<?php
class WhitelistGeneratorTest extends SapphireTest{

	protected static $fixture_file = 'WhitelistTest.yml';
	
	function testGenerateWhitelist(){
		$whitelist = WhitelistGenerator::generateWhitelistRules();

		$top1 = $this->objFromFixture('SiteTree', 'top1');
		$top2 = $this->objFromFixture('SiteTree', 'top2');
		$top3 = $this->objFromFixture('SiteTree', 'top3');
		$child1 = $this->objFromFixture('SiteTree', 'child1');
		$child2 = $this->objFromFixture('SiteTree', 'child2');
		$child3 = $this->objFromFixture('SiteTree', 'childchild1');
		$child4 = $this->objFromFixture('SiteTree', 'childchild2');
		$child5 = $this->objFromFixture('SiteTree', 'childchildchild1');
		
		$this->assertContains(trim($top1->relativeLink(),'/'), $whitelist);
		$this->assertContains(trim($top2->relativeLink(),'/'), $whitelist);
		$this->assertContains(trim($top3->relativeLink(),'/'), $whitelist);
		$this->assertNotContains(trim($child1->relativeLink(),'/'), $whitelist);
		$this->assertNotContains(trim($child2->relativeLink(),'/'), $whitelist);
		$this->assertNotContains(trim($child3->relativeLink(),'/'), $whitelist);
		$this->assertNotContains(trim($child4->relativeLink(),'/'), $whitelist);
		$this->assertNotContains(trim($child5->relativeLink(),'/'), $whitelist);

	}
	
	function testCustomControllerWhitelist() {
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
	
}

class WhitelistTestController extends ContentController {
	
	
}