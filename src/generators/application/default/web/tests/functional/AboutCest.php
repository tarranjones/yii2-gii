<?= "<?php\n"; ?>
namespace <?= $appName;?>\tests\functional;

use <?= $appName;?>\tests\FunctionalTester;

class AboutCest
{
    public function checkAbout(FunctionalTester $I)
    {
        $I->amOnRoute('site/about');
        $I->see('About', 'h1');
    }
}
