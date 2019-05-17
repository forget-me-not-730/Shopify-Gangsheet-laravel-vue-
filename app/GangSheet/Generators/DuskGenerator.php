<?php

namespace App\GangSheet\Generators;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Intervention\Image\Facades\Image as Image;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Chrome\ChromeProcess;

class DuskGenerator extends ImagickGenerator
{
    protected function driver()
    {
        $options = (new ChromeOptions)->addArguments([
            '--disable-gpu',
            '--headless',
            '--no-sandbox',
            '--disable-extensions',
            '--ignore-ssl-errors=yes',
            '--ignore-certificate-errors'
        ]);

        return RemoteWebDriver::create("http://localhost:{$this->chromeDriverPortNumber}",
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY,
                $options
            ),
            180 * 1000,
            180 * 1000
        );
    }

    /**
     * @throws \Exception
     */
    protected function createWebDriver()
    {
        return retry(5, function () {
            return $this->driver();
        }, 1000);
    }

    protected function newBrowser($driver): Browser
    {
        return new Browser($driver);
    }

    /**
     * @throws \Exception
     */
    private function createBrowser(): Browser
    {
        return $this->newBrowser($this->createWebDriver());
    }

    /**
     * @throws \Exception
     */
    public function browse(\Closure $callback): void
    {
        $process = null;
        $browser = null;

        Browser::$waitSeconds = 300;

        try {
            $process = (new ChromeProcess)->toProcess();
            $process->start();
            $browser = $this->createBrowser();

            $callback($browser);

        } finally {
            try {
                $browser?->quit();

                if ($process) {
                    $exitCode = $process->stop(3000);

                    if ($exitCode !== 0) {
                        $pId = $process->getPid();
                        exec("kill -9 $pId");
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    /**
     * @throws \Exception
     */
    function drawObjects(): void
    {
        $this->log('Browser is starting');
        $this->browse(function (Browser $browser) {

            $this->log("Visiting design URL: {$this->designUrl}");
            $browser->visit($this->designUrl)
                ->driver->manage()->timeouts()->pageLoadTimeout(30);
            $this->log('Waiting for canvas editor to be ready');
            $browser->waitUntil('window._gangSheetCanvasEditor && window._gangSheetCanvasEditor.isReady');

            $this->createCanvas();

            // max is 268435456
            $chromeCanvasMaxArea = 260000000;
            $segmentHeight = intval($chromeCanvasMaxArea / $this->width);

            $from = 0;
            while (1) {
                try {
                    $to = min($from + $segmentHeight, $this->height);

                    $this->log("Exporting segment from {$from} to {$to} -> $segmentHeight");
                    [$segment] = $browser->script("return _gangSheetCanvasEditor.exportGangSheet($from, $to)");

                    $this->log("Exported segment from {$from} to {$to}");
                    $imageSegment = Image::make($segment);
                    $this->canvas->insert($imageSegment, 'top-left', 0, $from);

                    $this->log("Inserted segment from {$from} to {$to}");
                    if ($to === $this->height) {
                        break;
                    }

                    $from += $imageSegment->height();
                    $segmentHeight = intval($chromeCanvasMaxArea / $this->width);
                } catch (\Throwable $e) {
                    $this->log("Error: {$e->getMessage()}");
                    $this->log("Retrying...");
                    $segmentHeight /= 2;
                }
            }
        });
    }

    public function buildThumbnail($design_id): bool
    {
        try {
            $this->browse(function (Browser $browser) {
                $designUrl = $this->designUrl;

                $browser->visit($designUrl);

                $browser->waitUntil('window._gangSheetCanvasEditor && window._gangSheetCanvasEditor.isReady', 300);

                [$content] = $browser->script('return _gangSheetCanvasEditor.exportThumbnail()');

                $thumbnail = explode(',', $content)[1];
                $thumbnail = base64_decode($thumbnail);
            });

            return true;
        } catch (\Throwable $e) {
            info($e);

            return false;
        }
    }
}
