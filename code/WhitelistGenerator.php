<?php
class WhitelistGenerator extends Object implements Flushable {

	static function generateWhitelist(){
		$whitelist = self::generateWhitelistRules();
		self::syncCacheFilesystem($whitelist);
	}
	
	static function generateWhitelistRules(){
		//get all URL rules
		$rules = Config::inst()->get('Director', 'rules');
		
		$allTopLevelRules = array();
		foreach($rules as $pattern => $controllerOptions) {
			//match first portion of the URL, either delimited by slash or colon or end-of-line
			if (preg_match('/^(.*?)(\/|:|$)/', $pattern, $matches)){
				if (!empty($matches[1])){
					array_push($allTopLevelRules, $matches[1]);
				}
			}
		}
		
		$filteredRules = array();
		foreach($allTopLevelRules as $rule) {
			if (strpos($rule, '$') !== false) {
				if ($rule === '$Controller') {
					//special case for Controllers, add all possible controllers
					$subControllers = ClassInfo::subclassesFor(new Controller());
					
					foreach($subControllers as $controller){
						array_push($filteredRules, $controller);    //add the controller name as a link
					} 
					
				} elseif ($rule === '$URLSegment') {
					//special case for SiteTree, add all possible top Level Pages
					$topLevelPages = SiteTree::get()->filter('ParentID', 0);
					
					foreach($topLevelPages as $page) {
						$link = $page->RelativeLink();
						array_push($filteredRules, trim($link, '\/ ')); //remove trailing or leading slashes from links
					}
					
				} else {
					user_error('Unknown wildcard URL match found: '.$rule, E_WARNING);
					
				}
			} else {
				//add the rule to a new list of rules
				array_push($filteredRules, $rule);
			}
			
		}

		//filter duplicates (order doesn't matter here, as we are only interested in the first level of the rules)
		$filteredRules = array_unique($filteredRules);
		return $filteredRules;
	}

	static protected function array_delete($array, $element) {
		return array_diff($array, [$element]);
	}

	/**
	 * Sync the list of all top-level routes with the file system whitelist cache
	 */
	static protected function syncCacheFilesystem($whitelist) {
		$dir = BASE_PATH . DIRECTORY_SEPARATOR . Config::inst()->get('WhitelistGenerator', 'dir');
		
		$whitelistFolderContents = scandir($dir);
		
		//create list of files to create
		$toCreate = array();
		foreach($whitelist as $listItem){
			if (!in_array($listItem, $whitelistFolderContents)) {
				if (!empty($listItem)) {    //don't include empty files, such as the file for /
					array_push($toCreate, $listItem);
				}
			}
		}

		//create list of files to delete
		$toDelete = array();
		foreach($whitelistFolderContents as $file){
			if (!in_array($file, array('','..','.','.htaccess'))) {    //exclude things that should stay in the folder 
				if (!in_array($file, $whitelist)) {
					array_push($toDelete, $file);
				}
			}
		}

		//delete files which are no longer necessary
		foreach($toDelete as $delete) {
			unlink($dir . DIRECTORY_SEPARATOR . $delete);
		}

		//create new whitelist items as files
		foreach($toCreate as $create) {
			touch($dir . DIRECTORY_SEPARATOR . $create);
		}
	}
	
	static function ensureWhitelistFolderExists(){
		$dir = BASE_PATH . DIRECTORY_SEPARATOR . Config::inst()->get('WhitelistGenerator', 'dir');
		if (!file_exists($dir)) {
			mkdir($dir); //create a new whitelist dir
			chmod($dir,0777);    //make sure it is readable by the web-server user
			//copy in htaccess file to ensure that the whitelist cache directory is not web-accessible 
			copy(BASE_PATH.DIRECTORY_SEPARATOR.'routewhitelist'.DIRECTORY_SEPARATOR.'extra'.DIRECTORY_SEPARATOR.'htaccess',
				$dir.DIRECTORY_SEPARATOR.'.htaccess');
		}
	}

	public static function flush() {
		self::ensureWhitelistFolderExists();    //only create folder on flush, not on sitetree changes
		self::generateWhitelist();
	}
}