<?php
if( ! class_exists( 'avada_lessc' ) ) {
	require_once('Less.php');
	
	class avada_lessc{

		static public $VERSION = avada_Less_Version::avada_Less_Version;

		public $importDir = '';
		protected $allParsedFiles = array();
		protected $libFunctions = array();
		protected $registeredVars = array();
		private $formatterName;

		public function __construct($lessc=null, $sourceName=null) {}

		public function setImportDir($dirs) {
			$this->importDir = (array)$dirs;
		}

		public function addImportDir($dir){
			$this->importDir = (array)$this->importDir;
			$this->importDir[] = $dir;
		}

		public function setFormatter($name)
		{
			$this->formatterName = $name;
		}

		public function setPreserveComments($preserve) {}
		public function registerFunction($name, $func) {
			$this->libFunctions[$name] = $func;
		}
		public function unregisterFunction($name) {
			unset($this->libFunctions[$name]);
		}

		public function setVariables($variables){
			foreach( $variables as $name => $value ){
				$this->setVariable( $name, $value );
			}
		}

		public function setVariable($name, $value){
			$this->registeredVars[$name] = $value;
		}

		public function unsetVariable($name){
			unset( $this->registeredVars[$name] );
		}

		public function parse($buffer, $presets = array()){
			$options = array();
			$this->setVariables($presets);

			switch($this->formatterName){
				case 'compressed':
					$options['compress'] = true;
					break;
			}

			$parser = new avada_Less_Parser($options);
			$parser->setImportDirs($this->getImportDirs());
			foreach ($this->libFunctions as $name => $func) {
				$parser->registerFunction($name, $func);
			}
			$parser->parse($buffer);
			if( count( $this->registeredVars ) ) $parser->ModifyVars( $this->registeredVars );

			return $parser->getCss();
		}

		protected function getImportDirs(){
			$dirs_ = (array)$this->importDir;
			$dirs = array();
			foreach($dirs_ as $dir) {
				$dirs[$dir] = '';
			}
			return $dirs;
		}

		public function compile($string, $name = null){

			$oldImport = $this->importDir;
			$this->importDir = (array)$this->importDir;

			$this->allParsedFiles = array();

			$parser = new avada_Less_Parser();
			$parser->SetImportDirs($this->getImportDirs());

			foreach ($this->libFunctions as $name => $func) {
				$parser->registerFunction($name, $func);
			}
			$parser->parse($string);

			if( count( $this->registeredVars ) ){
				$parser->ModifyVars( $this->registeredVars );
			}

			$out = $parser->getCss();

			$parsed = avada_Less_Parser::AllParsedFiles();
			foreach( $parsed as $file ){
				$this->addParsedFile($file);
			}

			$this->importDir = $oldImport;

			return $out;
		}

		public function compileFile($fname, $outFname = null) {
			if (!is_readable($fname)) {
				throw new Exception('load error: failed to find '.$fname);
			}

			$pi = pathinfo($fname);

			$oldImport = $this->importDir;

			$this->importDir = (array)$this->importDir;
			$this->importDir[] = realpath($pi['dirname']).'/';

			$this->allParsedFiles = array();
			$this->addParsedFile($fname);

			$parser = new avada_Less_Parser();
			$parser->SetImportDirs($this->getImportDirs());

			foreach ($this->libFunctions as $name => $func) {
				$parser->registerFunction($name, $func);
			}
			$parser->parseFile($fname);

			if( count( $this->registeredVars ) ) $parser->ModifyVars( $this->registeredVars );

			$out = $parser->getCss();

			$parsed = avada_Less_Parser::AllParsedFiles();
			foreach ($parsed as $file) {
				$this->addParsedFile($file);
			}

			$this->importDir = $oldImport;

			if ($outFname !== null) {
				return file_put_contents($outFname, $out);
			}

			return $out;
		}

		public function checkedCompile($in, $out) {
			if (!is_file($out) || filemtime($in) > filemtime($out)) {
				$this->compileFile($in, $out);
				return true;
			}
			return false;
		}


		/**
		 * Execute lessphp on a .less file or a lessphp cache structure
		 *
		 * The lessphp cache structure contains information about a specific
		 * less file having been parsed. It can be used as a hint for future
		 * calls to determine whether or not a rebuild is required.
		 *
		 * The cache structure contains two important keys that may be used
		 * externally:
		 *
		 * compiled: The final compiled CSS
		 * updated: The time (in seconds) the CSS was last compiled
		 *
		 * The cache structure is a plain-ol' PHP associative array and can
		 * be serialized and unserialized without a hitch.
		 *
		 * @param mixed $in Input
		 * @param bool $force Force rebuild?
		 * @return array lessphp cache structure
		 */
		public function cachedCompile($in, $force = false) {
			// assume no root
			$root = null;

			if (is_string($in)) {
				$root = $in;
			} elseif (is_array($in) && isset($in['root'])) {
				if ($force or ! isset($in['files'])) {
					// If we are forcing a recompile or if for some reason the
					// structure does not contain any file information we should
					// specify the root to trigger a rebuild.
					$root = $in['root'];
				} elseif (isset($in['files']) && is_array($in['files'])) {
					foreach ($in['files'] as $fname => $ftime ) {
						if (!file_exists($fname) || filemtime($fname) > $ftime) {
							// One of the files we knew about previously has changed
							// so we should look at our incoming root again.
							$root = $in['root'];
							break;
						}
					}
				}
			} else {
				// TODO: Throw an exception? We got neither a string nor something
				// that looks like a compatible lessphp cache structure.
				return null;
			}

			if ($root !== null) {
				// If we have a root value which means we should rebuild.
				$out = array();
				$out['root'] = $root;
				$out['compiled'] = $this->compileFile($root);
				$out['files'] = $this->allParsedFiles();
				$out['updated'] = time();
				return $out;
			} else {
				// No changes, pass back the structure
				// we were given initially.
				return $in;
			}
		}

		public function ccompile( $in, $out, $less = null) {
			if ($less === null) {
				$less = new self;
			}
			return $less->checkedCompile($in, $out);
		}

		public static function cexecute($in, $force = false, $less = null) {
			if ($less === null) {
				$less = new self;
			}
			return $less->cachedCompile($in, $force);
		}

		public function allParsedFiles() {
			return $this->allParsedFiles;
		}

		protected function addParsedFile($file) {
			$this->allParsedFiles[realpath($file)] = filemtime($file);
		}
	}
}