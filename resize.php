<?php
date_default_timezone_set('Asia/shanghai');
require 'vendor/autoload.php';


$aws_config= require('aws_config/config.php');
$key=$aws_config['aws_key'];
$secret=$aws_config['aws_secret'];
$bucketName=$aws_config['aws_bucket'];
$region=$aws_config['aws_region'];

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

$notFoundImg='404.jpg';

$src=$_GET['src'];

if(preg_match("/^(.*)_w(\d+)h(\d+).([a-z]+)$/",$src) || preg_match("/^(.*)_w(\d+).([a-z]+)$/", $src) || preg_match("/^(.*)_h(\d+).([a-z]+)$/", $src)) {
    if (preg_match("/^(.*)_w(\d+)h(\d+).([a-z]+)$/",$src, $matches)) {
        $objectName=$matches[1].'.'.$matches[4];
        $width=$matches[2];
        $height=$matches[3];
        $type=$matches[4];
        $originName=$matches[1];
    } elseif (preg_match("/^(.*)_w(\d+).([a-z]+)$/", $src, $matches)) {
        $objectName=$matches[1].'.'.$matches[3];
        $width=$matches[2];
        $height=null;
        $type=$matches[3];
        $originName=$matches[1];
    } else {
        preg_match("/^(.*)_h(\d+).([a-z]+)$/", $src, $matches);
        $objectName=$matches[1].'.'.$matches[3];
        $width=null;
        $height=$matches[2];
        $type=$matches[3];
        $originName=$matches[1];
    }

    $client = new S3Client([ 'region' => $region, 'version' => 'latest',
        'credentials' => [
            'key' => $key,
            'secret' => $secret
        ] ]);
    $client->registerStreamWrapper();

    try{
        $imagick = new \Imagick('s3://'.$bucketName.'/'.$objectName);
    }
    catch (Exception $e) {
        header('HTTP/1.1 404 Not Found');
        header("status:404 Not Found");
        header("Content-type: image/jpeg");
        echo file_get_contents($notFoundImg);
        die();
    }

    if($imagick){
        $contentType=$imagick->getImageMimeType();
        $originWidth=$imagick->getImageWidth();
        $originHeight=$imagick->getImageHeight();
        if ($width == null) {
            $width=intval(round(($originWidth / $originHeight) * $height));
            $putkey=$originName.'_'.'h'.$height.'.'.$type;
        } elseif ($height == null) {
            $height=intval(round(($originHeight / $originWidth) * $width));
            $putkey=$originName.'_'.'w'.$width.'.'.$type;
        } else {
            $putkey=$originName.'_'.'w'.$width.'h'.$height.'.'.$type;
        }

        $imagick->scaleImage($width,$height);

        header("Content-type: ".$contentType);
        echo $imagick->getImageBlob();

        $client->putObject([
            'ContentType' => '$contentType',
            'Body' =>$imagick->getImageBlob(),
            'Bucket' => $bucketName,
            'Key' => $putkey,
            'StorageClass' => 'REDUCED_REDUNDANCY',
            'Tagging' => 'thumbnail=yes'
        ]);
        die();
    }



}

header('HTTP/1.1 404 Not Found');
header("status:404 Not Found");
header("Content-type: image/jpeg");
echo file_get_contents($notFoundImg);