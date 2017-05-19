<?php

class MathJaxServer extends MathRenderer {

	protected $renderResult = "";

	protected function getMathTableName() {
		return 'math';
	}

	public function getHtmlOutput() {
		$domImg = new DOMDocument;
		$domImg->loadXML( $this->renderResult );

		$iWidth = ( int ) $domImg->documentElement->getAttribute( "width" ) * 8;
		$iHeight = ( int ) $domImg->documentElement->getAttribute( "height" ) * 8;

		//return $this->renderResult;
		//load svg into image
		$image = new Imagick();
		$image->readImageBlob( $domImg->saveXML() );
		$image->setImageFormat( "png24" );
		//save image to temp object after scaling failed on svg imported image
		$nImage = new Imagick();
		$nImage->readimageblob( $image->getimageblob() );
		$nImage->scaleImage( $iWidth, $iHeight, true );

		$attributes = [
			// the former class name was 'tex'
			// for backwards compatibility we keep that classname
			'class' => 'mwe-math-fallback-image-inline tex',
			'alt' => $this->getTex()
		];
		return Xml::element( 'img', $this->getAttributes(
			  'img', $attributes, [
				'src' => 'data:image/png;base64,' . base64_encode( $nImage->getimageblob() )
			  ]
			)
		);
	}

	public function render() {
		//start mathjax

		$arrData = [
			"format" => "TeX",
			"math" => $this->getUserInputTex(),
			"svg" => true,
			"mml" => false,
			"png" => false,
			"speakText" => true,
			"speakRuleset" => "mathspeak",
			"speakStyle" => "default",
			"ex" => 6,
			"width" => 1000000,
			"linebreaks" => false
		];
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'main' );
		$mathJaxServer = "http://" . $config->get( "MathJaxServerHost" ) . ":" . $config->get( "MathJaxServerPort" );

		$strData = json_encode( $arrData );

		$aOptions[ 'postData' ] = $strData;
		$this->renderResult = Http::post( $mathJaxServer, $aOptions );

		$bRet = false;
		if ( $this->renderResult === false ) {
			$bRet = false;
		} else {
			$bRet = true;
		}

		return $bRet;
	}

	protected function dbInArray() {
		return [ 'math_inputhash', 'math_outputhash',
			'math_html_conservativeness', 'math_html', 'math_mathml' ];
	}

	/**
	 * @param database_row $rpage
	 * @return bool
	 */
	protected function initializeFromDatabaseRow( $rpage ) {
		parent::initializeFromDatabaseRow( $rpage );
		// get deprecated fields
		if ( $rpage->math_outputhash ) {
			$dbr = wfGetDB( DB_SLAVE );
			$xhash = unpack( 'H32md5', $dbr->decodeBlob( $rpage->math_outputhash ) . "                " );
			$this->hash = $xhash[ 'md5' ];
			LoggerFactory::getInstance( 'Math' )->debug( 'Hashpath of PNG-File:' .
			  bin2hex( $this->hash ) );
			$this->conservativeness = $rpage->math_html_conservativeness;
			$this->html = $rpage->math_html;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Skip tex check for texvc rendering mode.
	 * Checking the tex code in texvc mode just adds a dependency to the
	 * texvccheck binary which does not improve security since the same
	 * checks are performed by texvc anyhow. Especially given the fact that
	 * texvccheck was derived from texvc.
	 * @return bool
	 */
	public function checkTeX() {
		return true;
	}

}
