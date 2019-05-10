<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Bundle\MailerBundle\Tests\DependencyInjection\Compiler;

use Fxp\Bundle\MailerBundle\DependencyInjection\Compiler\TemplatePass;
use Fxp\Component\Mailer\Loader\ArrayTemplateLayoutLoader;
use Fxp\Component\Mailer\Model\TemplateLayout;
use Fxp\Component\Mailer\Model\TemplateMail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests for template loader pass.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class TemplatePassTest extends KernelTestCase
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var TemplatePass
     */
    protected $pass;

    protected function setUp(): void
    {
        $this->rootDir = sys_get_temp_dir().'/fxp_mailer_bundle_template_loader_pass';
        $this->fs = new Filesystem();
        $this->pass = new TemplatePass();
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->rootDir);
        $this->pass = null;
    }

    public function testProcessWithoutService(): void
    {
        $container = $this->getContainer();

        $this->pass->process($container);
        $this->assertFalse($container->has('fxp_mailer.loader.template_layout_array'));
        $this->assertFalse($container->has('fxp_mailer.loader.template_mail_array'));
    }

    public function testProcessWithAddTemplates(): void
    {
        $container = $this->getContainer();
        $layoutLoaderDef = new Definition(ArrayTemplateLayoutLoader::class);
        $mailLoaderDef = new Definition(ArrayTemplateLayoutLoader::class);

        $layoutLoaderDef->setArguments([[]]);
        $mailLoaderDef->setArguments([[]]);

        $container->setDefinition('fxp_mailer.loader.template_layout_array', $layoutLoaderDef);
        $container->setDefinition('fxp_mailer.loader.template_mail_array', $mailLoaderDef);

        $this->assertCount(0, $layoutLoaderDef->getArgument(0));
        $this->assertCount(0, $mailLoaderDef->getArgument(0));

        // add mocks
        $layoutDef = new Definition(TemplateLayout::class);
        $mailDef = new Definition(TemplateMail::class);
        $layoutDef->addTag('fxp_mailer.layout');
        $mailDef->addTag('fxp_mailer.mail');

        $container->setDefinition('test.layout', $layoutDef);
        $container->setDefinition('test.mail', $mailDef);

        // test
        $this->pass->process($container);

        $this->assertCount(1, $layoutLoaderDef->getArgument(0));
        $this->assertCount(1, $mailLoaderDef->getArgument(0));
    }

    /**
     * Gets the container.
     *
     * @return ContainerBuilder
     */
    protected function getContainer()
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.cache_dir' => $this->rootDir,
            'kernel.debug' => false,
            'kernel.environment' => 'test',
            'kernel.name' => 'kernel',
            'kernel.root_dir' => $this->rootDir,
            'kernel.charset' => 'UTF-8',
            'kernel.bundles' => [],
        ]));
    }
}
