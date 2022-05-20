<?php

declare(strict_types=1);

namespace TechNikh\SculpinFacebookBundle;

use TechNikh\SculpinFacebookBundle\Command\SculpinFacebookCommand;
use TechNikh\SculpinFacebookBundle\DependencyInjection\SculpinFacebookExtension;
use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SculpinFacebookBundle extends Bundle
{
    public function registerCommands(Application $application)
    {
        $application->add(new SculpinFacebookCommand());
    }
}
