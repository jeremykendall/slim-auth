# Slim Auth [![Build Status](https://travis-ci.org/jeremykendall/slim-auth.png?branch=master)](https://travis-ci.org/jeremykendall/slim-auth)

Slim Auth is an authorization and authentication library for the [Slim Framework][1].
Authentication is provided by the Zend Framework [Zend\Authentication][2]
component, and authorization by the Zend Framework [Zend\Permissions\Acl][3] component.

## Slim Framework v3+

This is the Slim Auth version supporting Slim Framework version 3 and above.
For Slim Framework version 2 support, please see the [slim-2.x](https://github.com/jeremykendall/slim-auth/tree/slim-2.x) branch.

## Fair Warning: Documentation Mostly Complete

If you're familiar with [Zend\Authentication][2] and [Zend\Permissions\Acl][3],
you'll be able to implement the library without any trouble. Otherwise, you
might want to wait for the docs to be completed (no ETA) or open a GitHub issue
with any questions or problems you encounter.

Caveat emptor and all that.

## Requirements

Slim Auth works with all versions of Slim 3 and above.

## Example Implementation

An example implementation of Slim Auth for Slim 3 and above is not yet
available, although the unit tests and documentation should get you where you
need to go just fine.

## Installation

Installation is provided via [Composer][11].

First, install Composer.

``` bash
curl -s https://getcomposer.org/installer | php
```

Then install Slim Auth with the following Composer command.

``` bash
composer require jeremykendall/slim-auth
```

Finally, add this line at the top of your application’s index.php file:

``` php
require 'vendor/autoload.php';
```

## Preparing Your App For Slim Auth

### Slim Configuration

Slim Auth relies on [Middleware](http://www.slimframework.com/docs/concepts/middleware.html)
to do its job. More specifically it relies on being able to match the route
pattern being dispatched against resources defined in the ACL. That means the
`determineRouteBeforeAppMiddleware` setting MUST be set to `true` or the Slim
Auth middleware will not work.

``` php
$container = new \Slim\Container([
    'settings' => [
        'determineRouteBeforeAppMiddleware' => true,
    ],
]);
```

> Thanks to @urshofer and @pip8786 for #37 and pointing out that I'd left this
> critical bit out of the documentation. Sorry about that.

### Database

Your database should have a user table, and that table must have a `role`
column.  The contents of the `role` column should be a string and correspond to
the roles in your ACL. The table name and all other column names are up to you.

Here's an example schema for a user table. If you don't already have a user
table, feel free to use this one:

``` sql
CREATE TABLE IF NOT EXISTS [users] (
    [id] INTEGER NOT NULL PRIMARY KEY,
    [username] VARCHAR(50) NOT NULL,
    [role] VARCHAR(50) NOT NULL,
    [password] VARCHAR(255) NULL
);
```

### ACL

An Access Control List, or ACL, defines the set of rules that determines which group
of users have access to which routes within your Slim application. Below is a very simple example ACL. Please pay special attention to the comments.

*Please refer to the [Zend\Permissions\Acl documentation][3] for complete details on using the Zend Framework ACL component.*

``` php
namespace Example;

use Zend\Permissions\Acl\Acl as ZendAcl;

class Acl extends ZendAcl
{
    public function __construct()
    {
        // APPLICATION ROLES
        $this->addRole('guest');
        // member role "extends" guest, meaning the member role will get all of
        // the guest role permissions by default
        $this->addRole('member', 'guest');
        $this->addRole('admin');

        // APPLICATION RESOURCES
        // Application resources == Slim route patterns
        $this->addResource('/');
        $this->addResource('/login');
        $this->addResource('/logout');
        $this->addResource('/member');
        $this->addResource('/admin');

        // APPLICATION PERMISSIONS
        // Now we allow or deny a role's access to resources. The third argument
        // is 'privilege'. We're using HTTP method as 'privilege'.
        $this->allow('guest', '/', 'GET');
        $this->allow('guest', '/login', ['GET', 'POST']);
        $this->allow('guest', '/logout', 'GET');

        $this->allow('member', '/member', 'GET');

        // This allows admin access to everything
        $this->allow('admin');
    }
}
```

#### The Guest Role

Please note the `guest` role. **You must use the name** `guest` **as the role
assigned to unauthenticated users**. The other role names are yours to choose.

#### Acl "Privileges"

**IMPORTANT**: The third argument to `Acl::allow()`, 'privileges', is either a
string or an array, and should be an HTTP verb or HTTP verbs respectively. By
adding the third argument, you are restricting route access by HTTP method.  If
you do not provide an HTTP verb or verbs, you are allowing access to the
specified route via *all* HTTP methods. **Be extremely vigilant here.** You
wouldn't want to accidentally allow a 'guest' role access to an admin `DELETE`
route simply because you forgot to explicitly deny the `DELETE` route.

### Authentication Adapters

From the Zend Authentication documentation:

> `Zend\Authentication` adapters are used to authenticate against a particular
> type of authentication service, such as LDAP, RDBMS, or file-based storage.

Slim Auth provides an RDBMS authentication adapter for [PDO][12]. The constructor
accepts five required arguments:

* A `\PDO` instance
* The name of the user table
* The name of the identity, or username, column
* The name of the credential, or password, column
* An instance of `JeremyKendall\Password\PasswordValidator`

``` php
$db = new \PDO(<database connection info>);
$adapter = new PdoAdapter(
    $db,
    <user table name>,
    <identity column name>,
    <credential column name>,
    new PasswordValidator()
);
```

> **NOTE**: Please refer to the [Password Validator documentation][9] for more
> information on the proper use of the library. If you choose not to use the
> Password Validator library, you will need to create your own authentication
> adapter.

## Auth Handlers

Slim Auth uses handlers to determine what action to take based on
authentication and authorization status. Slim Auth provides two auth handlers
by default.

### ThrowHttpExceptionHandler

The `ThrowHttpExceptionHandler` will throw one of two `HttpException`s.
* If an unauthenticated request attempts to access a resource that requires
  authentication, an `HttpUnauthorizedException` will be thrown
    * Example: A visitor tries to visit their member profile page
    * Corresponds to an [`HTTP 401`](https://httpstatuses.com/401) status
* If an authenticated request attempts to access a resource that is not
  authorized for that request, an `HttpForbiddenException` will be thrown
    * Example: A member attempts to visit an admin page
    * Corresponds to an [`HTTP 403`](https://httpstatuses.com/403) status

These exceptions would probably best be handled by a custom [Slim Error Handler][13].

#### About the HttpException

The above exceptions implement the `HttpException` interface, which provides a `getStatusCode` method.
* `HttpUnauthorizedException::getStatusCode` returns `401`
* `HttpForbiddenException::getStatusCode` returns `403`

### RedirectHandler

The `RedirectHandler` allows you to specify redirect locations in response to
authentication and authorization status. The `RedirectHandler` constructor
takes two `string` arguments: `$redirectNotAuthenticated` and
`$redirectNotAuthorized`.

For example, a common use case is to redirect requests that should be
authenticated to a `/login` route and forbidden requests to a route that
informs the user what's happening, perhaps `/403`. The corresponding
`RedirectHandler` would be created like so:

``` php
$handler = new RedirectHandler('/login', '/403');
```

### Custom Auth Handlers

If neither of those handlers are appropriate for your use case, you can create
your own by implementing the [`AuthHandler`](src/Handlers/AuthHandler.php)
interface. Use the [existing auth handlers](src/Handlers) as a guide.

## Configuring Slim Auth

Now that you have a user database table with a `role` column, an Authentication
Adapter, and an ACL, you're ready to configure Slim Auth.

### Sample Container Service Configuration

Slim 3.x uses the [Pimple DI container by default][16], so this sample configuration
uses [Pimple][15] and the `\Slim\Container`.

``` php
$container = new \Slim\Container();
```

Make sure you add your `AuthAdapter` and `Acl` to your container.
* NOTE: The container key for the `AuthAdapter` MUST be named `authAdapter`.
* NOTE: The container key for the `Acl` MUST be named `acl`.

``` php
// ... snip ...

$container['authAdapter'] = function ($c) {
    $db = new \PDO(<database connection info>);
    $adapter = new \JeremyKendall\Slim\Auth\Adapter\Db\PdoAdapter(
        $db,
        <user table name>,
        <identity column name>,
        <credential column name>,
        new \JeremyKendall\Password\PasswordValidator()
    );

    return $adapter;
};

$container['acl'] = function ($c) {
    return new \Example\Acl();
};

// ... snip ...
```

Use the `SlimAuthProvider` to register the remaining Slim Auth services
on your container.

``` php
$container->register(new \JeremyKendall\Slim\Auth\ServiceProvider\SlimAuthProvider());
```

Finally add the Slim Auth middlware to your app.

``` php
$app->add($app->getContainer()->get('slimAuthRedirectMiddleware'));
```

NOTE: You may choose between the `slimAuthRedirectMiddleware` service (which
uses the RedirectHandler) or the `slimAuthThrowHttpExceptionMiddleware` service
(which uses the ThrowHttpExceptionHandler), or you can create your own handler
and register your own service.

### Overriding SlimAuthProvider Defaults

The following services/properties can be set _before_ calling
`\Slim\Container::register()` to override default settings.

* `$container['redirectNotAuthenticated']`
* `$container['redirectNotAuthorized']`
* `$container['authStorage']`

### Example Login Route

Using the `Authenticator` to authenticate users might be accomplished in this manner.

```php
$app->map(['GET', 'POST'], '/login', function ($request, $response, $args) {
    $params = $request->getParsedBody();

    if ($request->isPost()) {
        $username = $params['username'];
        $password = $params['password'];

        $result = $this->get('authenticator')->authenticate($username, $password);

        if ($result->isValid()) {
            // Success! Redirect somewhere.
        }

        // Login failed, handle error here
    }

    // Render login view here, perhaps.

    return $response;
});
```

### Example Logout Route

As authentication stores the authenticated user's identity, logging out
consists of nothing more than clearing that identity. Clearing the identity is
handled by `Authenticator::logout`.

``` php
$app->get('/logout', function ($request, $response, $args) {
    $this->get('authenticator')->logout();
    // Redirect somewhere after logout.
});
```

## And Done

That should get you most of the way. I'll complete documentation as soon as I'm
able, but can't currently commit to an ETA. Again, please feel free to open and
issue with any questions you might have regarding implementation.

Thanks for considering Slim Auth for your Slim 3.x project.

[1]: http://slimframework.com/
[2]: http://framework.zend.com/manual/current/en/modules/zend.authentication.intro.html
[3]: http://framework.zend.com/manual/current/en/modules/zend.permissions.acl.intro.html
[4]: http://docs.slimframework.com/#Route-Names
[5]: http://docs.slimframework.com/#Route-Helpers
[6]: https://packagist.org/packages/jeremykendall/slim-auth
[8]: https://github.com/ircmaxell/password_compat
[9]: https://github.com/jeremykendall/password-validator
[10]: https://github.com/jeremykendall/slim-auth-impl
[11]: http://getcomposer.org
[12]: http://php.net/manual/en/book.pdo.php
[13]: http://www.slimframework.com/docs/handlers/error.html
[14]: http://www.slimframework.com/docs/objects/router.html#route-names
[15]: http://pimple.sensiolabs.org/
[16]: http://www.slimframework.com/docs/concepts/di.html
