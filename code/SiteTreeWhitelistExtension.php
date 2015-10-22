<?php
class SiteTreeWhitelistExtension extends DataExtension {

	public function onAfterWrite(){
		//top level page change should trigger re-generation of whitelist
		if ($this->owner->getParentType() === 'root') {
			WhitelistGenerator::generateWhitelist();
		}
	}
	
	public function onAfterDelete(){
		if ($this->owner->getParentType() === 'root') {
			WhitelistGenerator::generateWhitelist();
		}
	}
	
}