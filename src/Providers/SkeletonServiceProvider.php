<?php

namespace NyonCode\Skeleton\Providers;

use NyonCode\LaravelPackageToolkit\Packager;
use NyonCode\LaravelPackageToolkit\PackageServiceProvider;
use Exception;


class SkeletonServiceProvider extends PackageServiceProvider
{
    /**
     * Configures the package. This method is called after the package is registered.
     *
     * @param Packager $packager
     *
     * @return void
     * @throws Exception
     */
    public function configure(Packager $packager): void
    {
        $packager->name('');
    }

}