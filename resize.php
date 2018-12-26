<?php
date_default_timezone_set('Asia/shanghai');
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

$bucketName='forum-images-test';

$notFoundImg='404.jpg';

$src=$_GET['src'];

if(preg_match("/^(.*)_(\d+)x(\d+)x(.*)\.([a-z]+)$/",$src,$matches)){

    $objectName=$matches[1].'.'.$matches[5];
    $width=$matches[2];
    $height=$matches[3];
    $dirfill=$matches[4];

    if(preg_match("/^(.*)\/(.*)$/",$matches[1],$mat)){
        $prefix=$mat[1];
        $putkey=$prefix.'/'.$dirfill.'/'.$mat[2].'.'.$matches[5];
    }else{
        $putkey=$dirfill.'/'.$matches[1].'.'.$matches[5];
    }

    print($putkey);

    $client = new S3Client([ 'region' => 'us-west-2', 'version' => 'latest',
        'credentials' => [
            'key' => 'xxx',
            'secret' => 'xxx'
        ] ]);
    $client->registerStreamWrapper();

    try{
        $imagick = new \Imagick('s3://'.$bucketName.'/'.$objectName);
    }
    catch (Exception $e) {
        $imagick= new \Imagick($notFoundImg);
    }

    if($imagick){
        $contentType=$imagick->getImageMimeType();

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

header("Content-type: image/jpeg");
echo file_get_contents($notFoundImg);