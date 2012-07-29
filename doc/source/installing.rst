.. vim: set tw=78 sw=4 ts=4 :

*****************
Installing Wrench
*****************

The library is PSR-0 compatible, with a vendor name of **Wrench**. An
SplClassLoader is bundled for convenience. The simplest possible bootstrap
looks like this::

    require_once 'SplClassLoader.php';

    $classLoader = new \SplClassLoader('Wrench', __DIR__ . '/../path/to/wrench/lib');
    $classLoader->register();

--------
composer
--------

Wrench is available on Packagist as `wrench/wrench <http://packagist.org/packages/wrench/wrench>`_.

Here's what it looks like in your :file:`composer.json`

.. code-block:: json

    {
        ...
        "require": {
            "wrench/wrench": "dev-master"
        }
    }

---------
deps file
---------

Using Symfony2 with a traditional style deps file? You can configure Wrench
like this:

.. code-block:: ini

    [wrench]
        git=git://github.com/varspool/Wrench.git
        version=origin/master
