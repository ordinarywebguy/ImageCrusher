<?php
/**
 * Image compression ran tru command line using pngcrush and jpegtran
 *
 * @author Mitchelle Pascual
 */
class ImageCrusher {

	private $imageType;
	private $source;
	private $destination;
	private $crushedFilename;
	
	const TMP_FILENAME_APPEND = '_tmp';
	
	/**
	 * pngcrush command statement
	 * 
	 * options:
	 * -rem alla
	 * 	removes all chunks except transparency
	 * -brute
	 *	tries more than 100 different methods for optimization
	 * -reduce
	 *	tries to reduce the number of colors if possible
	 * 
	 * @var const
	 */
	const PNGCRUSH_CMD = 'pngcrush -rem alla -brute -reduce %s %s';
	
	/**
	 * jpegtran command statement
	 * 
	 * options:
	 * -rem alla
	 * removes all chunks except transparency
	 * -brute
	 * tries more than 100 different methods for optimization
	 * -reduce
	 * tries to reduce the number of colors if possible
	 * 
	 * @var const
	 */
	const JPEGTRAN_CMD = 'jpegtran -copy none -optimize %s > %s';
	
	/**
	 * Constructor
	 * 
	 * @param string $source
	 * @param string $destination Empty string will create the temporary optimized image then delete the orginal file and rename it back.
	 * @param string $type Empty string will check for the extension filename from $source or $destination.
	 */
	public function __construct( $source, $destination = '', $type = '' ) {
		$this->source = $source;
		if( !empty( $destination ) ) {
			$this->destination = $destination;
		}  else {
			$this->crushedFilename = $source . self::TMP_FILENAME_APPEND;
		}
		$this->setImageType( $type );
	}

	/**
	 * Executes the legitimate image to optimize
	 */
	public function crush() {
		self::checkRequirements();
		
		switch( $this->imageType ) {
			case 'png':
				shell_exec( $this->command( self::PNGCRUSH_CMD ) );
				break;
			case 'jpg':
				shell_exec( $this->command( self::JPEGTRAN_CMD ) );
				break;
			default:
				// no image compression for gif images
				break;
		}
		$this->renameTmpToOrigFilename();
	}

	/**
	 * Renames the temporary compressed image to it's original filename
	 */
	protected function renameTmpToOrigFilename() {
		if( !empty( $this->crushedFilename ) ) {
			@unlink(  $this->source );
			@rename( $this->crushedFilename,  $this->source );
		}
	}
	
	/**
	 * Set the suitable command line statement
	 * 
	 * @param string $cmdStr
	 */
	protected function command( $cmdStr ) {
		if( !empty( $this->crushedFilename ) ) {
			return sprintf( $cmdStr, $this->source, $this->crushedFilename );
		} else {
			return sprintf( $cmdStr, $this->source, $this->destination );
		}
	}
	
	/**
	 * Sets the image type or get the extension filename from source or destination
	 * 
	 * @param string $imageType
	 */
	protected function setImageType( $imageType ) {
		$type = '';
		if( !empty( $imageType ) ) {
			$type = $imageType;
		} else {
			if( !empty( $this->destination ) ) {
				$filename = $this->destination;
			} else {
				$filename = $this->source;
			}
			$type = strtolower( end( explode( '.' , $filename ) ) );
		}
		$this->imageType = $type;
	}
	
	/**
	 * Checks if pngcrush and jpegtran are installed
	 * 
	 * @throws Exception
	 */
	public function checkRequirements() {
		//jpegtran is skipped since it always return null
		if( is_null( shell_exec( 'pngcrush ' ) ) ) {
			throw new Exception( 'Please install pngcrush and jpegtran first!' );
		}
	}
	
	/**
	 * Boolean requirements check
	 */
	public function isRequirementsInstalled() {
		try {
			self::checkRequirements();
		} catch( Exception $e ) {
			return false;
		} 
		return true;		
	}
	
	/**
	 * Batch images compression
	 * 
	 * @param string $directory
	 * @param boolean $isRecursive
	 */
	public static function batch( $directory, $isRecursive = false ) {
		if( !is_dir( $directory ) ) {
			throw new Exception( 'Directory "' . $directory . '" doesn\'t exist.' );
		}
		self::batchCompress( self::fetchImages( $directory, $isRecursive ) );
	}
	
	/**
	 * Gets all png|jpg images from defined directory
	 * 
	 * @param string $directory
	 * @param boolean $isRecursive
	 */
	protected function fetchImages( $directory, $isRecursive ) {
		$images = array();
		if( $isRecursive === false ) {
		  	$iterator = new DirectoryIterator( $directory );
		  	foreach ( $iterator as $fileinfo ) {
		  		if ( $fileinfo->isFile() && preg_match( '/\.(png|jpg)$/i', $fileinfo->getFilename() ) ) {
		  			array_push($images, $fileinfo->getPathname() );
		  		}
		  	}
		} else {
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory ) );
			while( $iterator->valid() ) {
			    if (!$iterator->isDot() && preg_match( '/\.(png|jpg)$/i', $iterator->current()->getFilename() ) ) {
				    array_push($images, $iterator->current()->getPathname() );
			    }
			    $iterator->next();
			}
		}
		return $images;
	}
	
	/**
	 * Does the compression of images
	 * 
	 * @param array $images
	 */
	protected function batchCompress( $images ) {
		foreach( $images as $image ) {
			$crusher = new self( $image );
			$crusher->crush();
		}
	}
	
	
	
	
	
	
	
	
	
	
}
