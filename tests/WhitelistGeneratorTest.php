<?php
class WhitelistGeneratorTest extends SapphireTest{

	protected static $fixture_file = 'WhitelistTest.yml';
	
	function testGenerateWhitelist(){

		$whitelist = WhitelistGenerator::generateWhitelistRules();
		Debug::Show($whitelist);

		$top1 = $this->objFromFixture('SiteTree', 'top1');
		$top2 = $this->objFromFixture('SiteTree', 'top2');
		$top3 = $this->objFromFixture('SiteTree', 'top3');
		$child1 = $this->objFromFixture('SiteTree', 'child1');
		$child2 = $this->objFromFixture('SiteTree', 'child2');
		$child3 = $this->objFromFixture('SiteTree', 'childchild1');
		$child4 = $this->objFromFixture('SiteTree', 'childchild2');
		$child5 = $this->objFromFixture('SiteTree', 'childchildchild1');
		
		
		
	}
	
}

class WhitelistTestController extends ContentController {
	
	
}