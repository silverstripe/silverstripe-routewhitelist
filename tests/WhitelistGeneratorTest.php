<?php
class WhitelistGeneratorTest extends SapphireTest {

	protected static $fixture_file = 'WhitelistTest.yml';

    public function setUp() {
        WhitelistGenerator::ensureWhitelistFolderExists();
        parent::setUp();
    }

	public function testGenerateWhitelist(){
		$whitelist = WhitelistGenerator::generateWhitelistRules();

		$top1 = $this->objFromFixture('SiteTree', 'top1');
		$top2 = $this->objFromFixture('SiteTree', 'top2');
		$top3 = $this->objFromFixture('SiteTree', 'top3');
		$child1 = $this->objFromFixture('SiteTree', 'child1');
		$child2 = $this->objFromFixture('SiteTree', 'child2');
		$child3 = $this->objFromFixture('SiteTree', 'childchild1');
		$child4 = $this->objFromFixture('SiteTree', 'childchild2');
		$child5 = $this->objFromFixture('SiteTree', 'childchildchild1');

		$this->assertContains(trim($top1->relativeLink(), '/'), $whitelist);
		$this->assertContains(trim($top2->relativeLink(), '/'), $whitelist);
		$this->assertContains(trim($top3->relativeLink(), '/'), $whitelist);
		$this->assertNotContains(trim($child1->relativeLink(), '/'), $whitelist);
		$this->assertNotContains(trim($child2->relativeLink(), '/'), $whitelist);
		$this->assertNotContains(trim($child3->relativeLink(), '/'), $whitelist);
		$this->assertNotContains(trim($child4->relativeLink(), '/'), $whitelist);
		$this->assertNotContains(trim($child5->relativeLink(), '/'), $whitelist);
	}

    public function testWhitelistAfterDelete() {
        $dir = BASE_PATH . DIRECTORY_SEPARATOR . Config::inst()->get('WhitelistGenerator', 'dir');
        $whitelist = WhitelistGenerator::generateWhitelistRules();
        $top1 = $this->objFromFixture('SiteTree', 'top1');
        $path = $dir . '/' . $top1->URLSegment;

        //Check that relevant file exists in cache directory of checks
        $this->assertTrue(file_exists($path));

        //Now assert that the same file has been rightfully deleted
        $top1->delete();
        $this->assertFalse(file_exists($path));
    }

    private function getFilesFromCacheDir() {
        $dir = BASE_PATH . DIRECTORY_SEPARATOR . Config::inst()->get('WhitelistGenerator', 'dir');
        $files = array();
        if ($handle = opendir($dir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..') {
                    array_push($files, $entry);
                }
            }
        }
        return $files;
    }

    public function testArrayDelete() {
        $sub = new SubWhitelistGenerator();
        $sourceArray = array(1, 2, 3, 4);
        $this->assertEquals(
            array(1, 2, 4),
            array_values($sub->callArrayDelete($sourceArray, 3))
        );

        $this->assertEquals(
            array(1, 2, 3, 4),
            $sub->callArrayDelete($sourceArray, 6)
        );
    }

    public function testWhitelistAfterUnpublish() {
        $dir = BASE_PATH . DIRECTORY_SEPARATOR . Config::inst()->get('WhitelistGenerator', 'dir');
        $whitelist = WhitelistGenerator::generateWhitelistRules();
        $top1 = $this->objFromFixture('SiteTree', 'top1');
        $path = $dir . '/' . $top1->URLSegment;

        //Check that relevant file exists in cache directory of checks
        $this->assertTrue(file_exists($path));

        //Now assert that the same file has not been deleted, still exists on Stage
        $top1->doUnpublish();
        $this->assertTrue(file_exists($path));
    }

    public function testClearWhitelist() {
        WhitelistGenerator::generateWhitelistRules();
        $files = $this->getFilesFromCacheDir();
        // Exact number depends on what addons are installed, so just go with 'some'
        $this->assertGreaterThan(90, sizeof($files));

        WhitelistGenerator::clearWhitelist();

        // clear the whitelist, only .htaccess should remain
        $files = $this->getFilesFromCacheDir();
        $this->assertEquals(1, sizeof($files));
    }

	public function testCustomControllerWhitelist() {
		$whitelist = WhitelistGenerator::generateWhitelistRules();

		//test that custom class defined below is included in the whitelist
		$this->assertContains('WhitelistTestController', $whitelist);
	}

	function testSiteTreeVersionsIncludedInWhitelist() {
		$top1 = $this->objFromFixture('SiteTree', 'top1');
		$top1->publish('Stage', 'Live');    //publish the page so it has been live and needs redirecting to

		$newSegment = 'new-url-segment';
		$oldSegment = $top1->URLSegment;
		$top1->URLSegment = $newSegment;
		$top1->write();
		$top1->publish('Stage', 'Live');    //publish again with a new URL


		$whitelist = WhitelistGenerator::generateWhitelistRules();

		//ensure both the old and the new URLs are included in the whitelist
		$this->assertContains($newSegment, $whitelist);
		$this->assertContains($oldSegment, $whitelist);
	}

    public function testFlush() {
        $dir = BASE_PATH . DIRECTORY_SEPARATOR . Config::inst()->get('WhitelistGenerator', 'dir');

        //Delete cache directory
        WhitelistGenerator::clearWhitelist();
        array_map('unlink', glob("$dir/.htaccess"));
        rmdir($dir);

        //Flush
        WhitelistGenerator::flush();
        $files = $this->getFilesFromCacheDir();

        // Exact number depends on what addons are installed, so just go with 'some'
        $this->assertGreaterThan(90, sizeof($files));
    }

}

class WhitelistTestController extends ContentController implements TestOnly {
}

class SubWhitelistGenerator extends WhitelistGenerator implements TestOnly {
    public function callArrayDelete($array, $element) {
        return $this->array_delete($array, $element);
    }
}
