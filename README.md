# Important Note

I am not yet convinced that what this library does is a meaningful improvement to security, as there are many other ways
for an attacker with write access to a database to cause significant damage and to exfiltrate secrets. As it stands,
this is mainly an attempt to get some thoughts "down on paper", as it were. If I'm convinced of its value in a production
setting and can confidently vouch for its security benefits, then I'll finish the packaging process and tag a stable
release.

# LaraLock

LaraLock is a utility for Laravel projects to protect against injected password credentials. If an attacker is somehow
able to write values to your database, be it through SQL injection, having access to the database server, or some other
avenue, simply hashing your passwords is no longer enough. While the hashed passwords are theoretically unable to be
cracked, an attacker with write access to your database can simply hash a new password and write it into any user record.

LaraLock protects your application passwords by building on top of password hashing with an Authenticated Encryption
with Additional Data (AEAD) scheme. With this, you start with the security guarantees of using a proper password hash.
Next, the password hashes are encrypted with a key known only to the application. This prevents an attacker from
generating a freshly hashed password and being able to insert it into your database without somehow also having to
compromise the encryption key. Finally, the cipher text has an authentication code attached to it that includes some
row-specific data in its calculation. This prevents the attacker from using the application to generate an encrypted
hash with a known value and then substituting that value into another user's record.

## Configuration

Start by publishing the LaraLock configuration file with `php artisan vendor:publish`. Then generate a new encryption
key with `php artisan laralock:key:generate`. Once that's done, you'll need to change your auth configuration to use
LaraLock's user provider. Find the provider that you're using in `config/auth.php` and change its driver to 'laralock'.
The provider inherits from Laravel's `EloquentUserProvider` and uses the same configuration values.

There is one additional, optional configuration value that may be set in your providers array: 'fallthrough'. If unset,
it defaults to `false`. When `true`, the user provider will attempt to use your stored password hashes as-is without
decryption if it fails to decrypt the value. This completely negates the protections afforded by this library, but is
provided as a way to temporarily turn off enforcement for maintenance reasons.

Your `config/auth.php` file should look something like this:

```php
    'providers' => [
        'users' => [
            'driver' => 'laralock',
            'model' => App\User::class,
            'fallthrough' => false, // Optional
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],
```

LaraLock transparently uses Laravel's normal hashing mechanisms. You are free to change your hashing settings in
`config/hashing.php` while using LaraLock as you normally would.

Warning: Your encrypted password hashes will be longer than unencrypted hashes are. Make sure you have room in your
password column before using this. It's generally a good idea to have the column be a VARCHAR(255) for future-proofing
anyway.

## Usage

Once configured, your application will automatically start decrypting and validating authentication codes when your
users log in. All that remains is to ensure that their hashed passwords are encrypted. LaraLock provides a facade with
an interface similar to Laravel's `Hash` facade that you are encouraged to use instead of `Hash`:

```php
use MCordingley\LaraLock\LaraLock;

// Hash Replacement Helpers
LaraLock::check(string $value, Authenticatable $user, array $options = [])
LaraLock::make(string $value, Authenticatable $user, array $options = [])
LaraLock::needsRehash(string $value, Authenticatable $user, array $options = [])
```

Note how each method also requires a user instance. This is so the calculations can lock the encrypted passwords to
their host rows in the database. Internally, they use `$user->getAuthIdentifier()` to get the additional data for
authenticating the cipher text. By default, Laravel provides the model's primary key for this, which is normally the `id` column.

Warning: Unless you have an unusual setup, your user's auth identifier will only exist AFTER the user has been saved to
the database. When creating a new user, you will have to save your user without a password or with a dummy value and
then update the user with the password hash.

The facade also provides two additional helpers:

```php
// Encryption Helpers
LaraLock::decrypt(string $value, string $additionalData, bool $unserialize = true)
LaraLock::encrypt($value, string $additionalData, bool $serialize = true)
```

These are provided to migrate existing passwords into LaraLock or if you need to change your user auth identifier. Both
include the option to (un)serialize the plaintext to stay as close as possible to the method signature of Laravel's
stock `encrypt()` and `decrypt()` methods. For password use, you will always want to set these values to `false`.

### Migrating Existing Records

To migrate your existing password hashes into LaraLock, it's sufficient to run the following snippet of code, either
inside of an Artisan command or through Tinker:

```php
foreach (\App\User::all() as $user) {
    $user->password = LaraLock::encrypt($user->password, $user->getAuthIdentifier(), false);
    $user->save();
}
```

### Rotating the Key

If for some reason you need to change your encryption key or the additional data used to lock your passwords, you'll
need to first decrypt all of the rows with the old key and data before encrypting with the new key and data. At a high
level, you'll need to perform the following steps:

1. If this process will take a while, you may wish to start by turning `fallthrough` on to minimize disruption to your
   users.
2. Loop through and decrypt all records with your current settings.
3. Change your key, auth identifier, or whatever else is prompting this process.
4. Loop through and encrypt all records with your new settings.
5. If enabled, turn `fallthrough` off to start protecting your users again.