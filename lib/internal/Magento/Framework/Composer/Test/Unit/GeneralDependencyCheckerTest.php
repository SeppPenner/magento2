<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Composer\Test\Unit;

use Magento\Framework\Composer\GeneralDependencyChecker;

class GeneralDependencyCheckerTest extends \PHPUnit_Framework_TestCase
{
    public function testCheckDependencies()
    {
        $composerApp = $this->getMock('Composer\Console\Application', [], [], '', false);
        $directoryList = $this->getMock('Magento\Framework\App\Filesystem\DirectoryList', [], [], '', false);
        $directoryList->expects($this->exactly(2))->method('getRoot');
        $composerApp->expects($this->once())->method('setAutoExit')->with(false);

        $composerApp->expects($this->at(1))->method('run')->willReturnCallback(
            function ($input, $buffer) {
                $output = 'magento/package-b requires magento/package-a (1.0)' . PHP_EOL .
                    'magento/package-c requires magento/package-a (1.0)' . PHP_EOL;
                $buffer->writeln($output);
            }
        );
        $composerApp->expects($this->at(2))->method('run')->willReturnCallback(
            function ($input, $buffer) {
                $output = 'magento/package-c requires magento/package-b (1.0)' . PHP_EOL .
                    'magento/package-d requires magento/package-b (1.0)' . PHP_EOL;
                $buffer->writeln($output);
            }
        );

        $generalDependencyChecker = new GeneralDependencyChecker($composerApp, $directoryList);
        $expected = [
            'magento/package-a' => ['magento/package-b', 'magento/package-c'],
            'magento/package-b' => ['magento/package-c', 'magento/package-d'],
        ];
        $this->assertEquals(
            $expected,
            $generalDependencyChecker->checkDependencies(['magento/package-a', 'magento/package-b'])
        );
    }

    public function testCheckDependenciesExcludeSelf()
    {
        $composerApp = $this->getMock('Composer\Console\Application', [], [], '', false);
        $directoryList = $this->getMock('Magento\Framework\App\Filesystem\DirectoryList', [], [], '', false);
        $directoryList->expects($this->exactly(3))->method('getRoot');
        $composerApp->expects($this->once())->method('setAutoExit')->with(false);

        $composerApp->expects($this->at(1))->method('run')->willReturnCallback(
            function ($input, $buffer) {
                $output = 'magento/package-b requires magento/package-a (1.0)' . PHP_EOL .
                    'magento/package-c requires magento/package-a (1.0)' . PHP_EOL;
                $buffer->writeln($output);
            }
        );
        $composerApp->expects($this->at(2))->method('run')->willReturnCallback(
            function ($input, $buffer) {
                $output = 'magento/package-c requires magento/package-b (1.0)' . PHP_EOL .
                    'magento/package-d requires magento/package-b (1.0)' . PHP_EOL;
                $buffer->writeln($output);
            }
        );
        $composerApp->Expects($this->at(3))->method('run')->willReturnCallback(
            function ($input, $buffer) {
                $output = 'magento/package-d requires magento/package-c (1.0)' . PHP_EOL;
                $buffer->writeln($output);
            }
        );

        $generalDependencyChecker = new GeneralDependencyChecker($composerApp, $directoryList);
        $expected = [
            'magento/package-a' => [],
            'magento/package-b' => ['magento/package-d'],
            'magento/package-c' => ['magento/package-d'],
        ];
        $this->assertEquals(
            $expected,
            $generalDependencyChecker->checkDependencies(
                ['magento/package-a', 'magento/package-b', 'magento/package-c'],
                true
            )
        );
    }
}
