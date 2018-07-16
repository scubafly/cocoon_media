<?php

namespace Drupal\cocoon_media_management;

/**
 * @Plugin(
 *
 * )
 */
class CocoonController {
	public static $domainName = 'use-cocoon.com';

	public $thumbsPerPage = 24;
	public $subdomain = '';
	public $username = '';
	public $secretkey = '';

	public function __construct( $sub_domain, $user_name, $secret_key) {
		$this->subdomain = $sub_domain;
		$this->username = $user_name;
		// $requestId = $reqId;
		$this->secretkey =$secret_key;
  }

	public static function SoapClient( $reqId, $sub_domain, $user_name, $secret_key) {
		$domainName = self::$domainName;
		$requestId = $reqId;
		$wsdl = "https://{$sub_domain}.{$domainName}/webservice/wsdl";

		$hash = sha1( $sub_domain . $user_name . $requestId . $secret_key );

		$oAuth = new \StdClass();
		$oAuth->username = $user_name;
		$oAuth->requestId = $requestId;
		$oAuth->hash = $hash;

		$oSoapClient = new \SoapClient( $wsdl );
		$SoapHeader = new \SoapHeader( 'auth', 'authenticate', $oAuth );
		$oSoapClient->__setSoapHeaders( $SoapHeader );

		return $oSoapClient;
	}

	public function getThumbTypes() {
		try {
			$output = self::SoapClient(
				$this->getRequestId(),
				$this->subdomain,
				$this->username,
				$this->secretkey)->getThumbtypes();
		} catch ( SoapFault $oSoapFault ) {
			$output = $oSoapFault;
		}

		return $output;
	}

	public function getSets() {
		try {
			$output = self::SoapClient(
				$this->getRequestId(),
				$this->subdomain,
				$this->username,
				$this->secretkey)->getSets();
		} catch ( SoapFault $oSoapFault ) {
			$output = $oSoapFault;
		}

		return $output;
	}

	public function getFilesBySet( $setId ) {
		try {
			$output = self::SoapClient(
				$this->getRequestId(),
				$this->subdomain,
				$this->username,
				$this->secretkey)->getFilesBySet( $setId );
		} catch ( SoapFault $oSoapFault ) {
			$output = $oSoapFault;
		}

		return $output;
	}

	public function getFile( $fileId ) {
		try {
			$output = self::SoapClient(
				$this->getRequestId(),
				$this->subdomain,
				$this->username,
				$this->secretkey)->getFile( $fileId );
		} catch ( SoapFault $oSoapFault ) {
			$output = $oSoapFault;
		}

		return $output;
	}

	public function getThumbInfo( $fileId ) {
		$subDomain  = $this->subdomain;
		$domainName = self::$domainName;
		$url        = "https://{$subDomain}.{$domainName}";
		$thumbOrg   = 'original';
		$thumbWeb   = '400px';

		$noThumb = true;

		$aThumbTypes  = $this->getThumbTypes();
		$thumbOrgPath = $aThumbTypes[ $thumbOrg ]['path'];
		$thumbWebPath = $aThumbTypes[ $thumbWeb ]['path'];

		$aFile     = $this->getFile( $fileId );
		$filename  = $aFile['filename'];
		$extention = strtolower( $aFile['extension'] );

		if ( $extention === 'jpg' ||
		     $extention === 'jpeg' ||
		     $extention === 'png' ||
		     $extention === 'gif' ||
		     $extention === 'tiff' ||
		     $extention === 'tif' ||
		     $extention === 'bmp'
		) {
			$noThumb = false;
		}

		$fileDim  = $aFile['width'] && $aFile['height'] ? $aFile['width'] . ' x ' . $aFile['height'] : '';
		$fileSize = $aFile['size'] ? round( $aFile['size'] / 1024 ) . ' KB' : '';

		if ( $aFile['upload_date'] ) {
			$date         = date_create( $aFile['upload_date'] );
			$fileUploaded = $date;
		} else {
			$fileUploaded = '';
		}

		return array(
			'path'     => $url . $thumbOrgPath . '/' . $filename . '.' . $extention,
			'web'      => ! $noThumb ? $url . $thumbWebPath . '/' . $filename . '.jpg' : '',
			'ext'      => $extention,
			'name'     => $filename,
			'dim'      => $fileDim,
			'size'     => $fileSize,
			'uploaded' => $fileUploaded,
			'domain'   => $url
		);
	}

	public function getRequestId() {
		return (string) microtime( true );
	}

	private function errorResponse( $errMsg ) {
		return json_encode( array( 'status' => 'error', 'statusMsg' => $errMsg ) );
	}

	public function getVersion() {
		try {
			$output = self::SoapClient(
				$this->getRequestId(),
				$this->subdomain,
				$this->username,
				$this->secretkey)->getVersion();
		} catch ( SoapFault $oSoapFault ) {
			$output = $oSoapFault;
		}

		return $output;
	}
}
