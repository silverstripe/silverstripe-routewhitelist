<?php
class WhitelistGenerator extends Object implements Flushable {

	public static function generateWhitelist(){
		$whitelist = self::generateWhitelistRules();
		self::syncCacheFilesystem($whitelist);
	}

	public static function generateWhitelistRules(){
		//get all URL rules
		$rules = Config::inst()->get('Director', 'rules');

		$allTopLevelRules = array();
		foreach ($rules as $pattern => $controllerOptions) {
			//allow for route rules starting with double-slash (//)
			$pattern = ltrim($pattern, '/!');

			//match first portion of the URL, either delimited by slash or colon or end-of-line
			if (preg_match('/^(.*?)(\/|:|$)/', $pattern, $matches)){
				if (!empty($matches[1])){
					array_push($allTopLevelRules, $matches[1]);
				}
			}
		}
		$filteredRules = array('home'); //add 'home' url, as default
		
		$addToWhitelist = Config::inst()->get('WhitelistGenerator', 'addToWhitelist');
		if ($addToWhitelist && is_array($addToWhitelist)) {
			$filteredRules = array_merge($filteredRules, $addToWhitelist);
		}
		
		foreach($allTopLevelRules as $rule) {
			if (strpos($rule, '$') !== false) {
				if ($rule === '$Controller') {
					//special case for Controllers, add all possible controllers
					$subControllers = ClassInfo::subclassesFor(new Controller());

					foreach ($subControllers as $controller){
						array_push($filteredRules, $controller);    //add the controller name as a link
					}

				} elseif ($rule === '$URLSegment') {
					$topLevelPagesArray = array();  //temporary array to store top-level pages
					$oldTopLevelPagesArray = array();

					//special case for SiteTree, add all possible top Level Pages
					$topLevelPages = SiteTree::get()->filter('ParentID', 0);

					foreach ($topLevelPages as $page) {
						$link = $page->RelativeLink();
						array_push($topLevelPagesArray, trim($link, '\/ ')); //remove trailing or leading slashes from links
					}

					//fetch old top-level pages URLs from the SiteTree_versions table
					if (Config::inst()->get('WhitelistGenerator', 'includeSiteTreeVersions')) {
						$oldTopLevelPagesArray = self::find_old_top_level_pages($topLevelPagesArray);
					}

					$filteredRules = array_merge($filteredRules, $topLevelPagesArray, $oldTopLevelPagesArray);
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

		$removeFromWhitelist = Config::inst()->get('WhitelistGenerator', 'removeFromWhitelist');
		if ($removeFromWhitelist && is_array($removeFromWhitelist)) {
			$filteredRules = array_merge(array_diff($filteredRules, $removeFromWhitelist));
		}

		return $filteredRules;
	}

	protected static function array_delete($array, $element) {
        $elementArray = array($element);
		return array_diff($array, $elementArray);
	}

	/**
	 * Sync the list of all top-level routes with the file system whitelist cache
	 */
	protected static function syncCacheFilesystem($whitelist) {
		$dir = BASE_PATH . DIRECTORY_SEPARATOR . Config::inst()->get('WhitelistGenerator', 'dir');

		$whitelistFolderContents = scandir($dir);

		//create list of files to create
		$toCreate = array();
		foreach ($whitelist as $listItem){
			if (!in_array($listItem, $whitelistFolderContents)) {
				if (!empty($listItem)) {    //don't include empty files, such as the file for /
					array_push($toCreate, $listItem);
				}
			}
		}

		//create list of files to delete
		$toDelete = array();
		foreach ($whitelistFolderContents as $file){
			if (!in_array($file, array('','..','.','.htaccess'))) {    //exclude things that should stay in the folder
				if (!in_array($file, $whitelist)) {
					array_push($toDelete, $file);
				}
			}
		}

		//delete files which are no longer necessary
		foreach ($toDelete as $delete) {
			unlink($dir . DIRECTORY_SEPARATOR . $delete);
		}

		//create new whitelist items as files
		foreach ($toCreate as $create) {
			touch($dir . DIRECTORY_SEPARATOR . $create);
		}
	}

	/**
	 * Does a database query searching through past URLs of top-level pages, returning any URLs previously used for
	 * pages in the SiteTree. This is to ensure that OldPageRedirector rules still apply correctly. That is, to ensure
	 * that pages that have been renamed continue to redirect to their current versions, we add the pages' old URLs
	 * to the whitelist.
	 * @param $currentTopLevelPages
	 * @return array URLs of past top-level pages
	 */
	protected static function find_old_top_level_pages($currentTopLevelPages){
		$oldPageURLs = array();

		$queryClass = 'SQLSelect';
		if (!class_exists($queryClass) && class_exists('SQLQuery')){
			$queryClass = 'SQLQuery';
		}
		
		$query = new $queryClass(
			'DISTINCT (URLSegment)',
			'SiteTree_versions',
			array(
				'ParentID = 0',
				'WasPublished = 1',
				'URLSegment NOT IN (\''.implode("','",array_filter($currentTopLevelPages)).'\')'
			)
		);

		$records = $query->execute();
		if ($records) {
			foreach($records as $record) {
				array_push($oldPageURLs, $record['URLSegment']);
			}
		}

		return $oldPageURLs;
	}

    public static function ensureWhitelistFolderExists(){
		$dir = BASE_PATH . DIRECTORY_SEPARATOR . Config::inst()->get('WhitelistGenerator', 'dir');
		if (!file_exists($dir)) {
			mkdir($dir); //create a new whitelist dir
			chmod($dir,0777);    //make sure it is readable by the web-server user
			//create a htaccess file to ensure that the whitelist cache directory is not web-accessible
			file_put_contents($dir.DIRECTORY_SEPARATOR.'.htaccess', "Deny from all\n");
		}
	}

	public static function clearWhitelist(){
		$dir = BASE_PATH . DIRECTORY_SEPARATOR . Config::inst()->get('WhitelistGenerator', 'dir');
		if (file_exists($dir)) {
			array_map('unlink', glob($dir."/*"));
		}
	}

	public static function flush() {
		self::ensureWhitelistFolderExists();    //only create folder on flush, not on sitetree changes
		self::generateWhitelist();
	}
}
