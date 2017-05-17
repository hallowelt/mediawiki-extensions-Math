<?php

class MathJax extends MathRenderer {

	protected $renderResult = "";

	protected function getMathTableName() {
		return 'math';
	}

	public function getHtmlOutput() {
		return $this->renderResult;
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

		$aOptions['postData'] = $strData;
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
