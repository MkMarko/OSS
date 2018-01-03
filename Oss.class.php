<?php
/**
 *
 *
 * 	
 *	1.引入Oss SDK; 
		composer require aliyuncs/oss-sdk-php 
		composer install   安装依赖
	2.在入口文件引入自动加载文件
		require_once __DIR__ . '/vendor/autoload.php';
	3.use命名空间 
		use OSS\OssClient;
		use OSS\Core\OssException;
 *
 *
 *
 *
 * 
 */
namespace Admin\Controller\Article;
class Oss
{

	/** 
	 * OSS 上传图片
	 * @param  OSS对象
	 * @param  OSS库名
	 * @param  需要上传的文件 把$_FILES['img']传入
	 * @param  【可选】 需要上传的文件夹
	 * @param  【可选】 需要保存的名字
	 * @return 数组 Error=0 成功 Error=1错误 Message获取错误信息 Info获取文件信息
	 * 
	 * 调用方法 ： $up = $this->uploadFile($ossClient,$setting['bucket'],$_FILES['fileimg']);
	 */
	public function uploadFile($ossClient, $bucket,$file,$pre='abc/',$savename='')
	{
		$arr['Error'] = 0;
		try {
			$qie = explode('.',$file['name']);
		    if($savename == '') $savename = md5(time().date('Y-m-d H:i:s')).'.'.$qie[count($qie)-1];
		    $filePath = $file['tmp_name'];
	        $obj = $ossClient->uploadFile($bucket, $pre.$savename, $filePath,$options);
		} catch (OssException $e) {
			$arr['Error'] = 1;
			$arr['Message'] = $e->getMessage();
	        return $arr;
		}
	
	    $arr['Info'] = $obj['info'];
	    return $arr;
	}


	/**
	 * OSS 文件列表
	 * @param  OSS对象
	 * @param  OSS库名
	 * @param  【可选】 拼接链接 该选项如果没有填则不返回URL
	 * @param  【可选】 选项: pre为文件名  max为最多取多少条
	 * @return 数组 Error=0 成功 Error=1错误 Message获取错误信息 List获取文件列表
	 *
	 * 调用方法 : $list = $this->listFile($ossClient,$setting['bucket']);
	 */
	public function listFile($ossClient,$bucket,$url='',$option=['pre'=>'abc/','max'=>'20','marker'=>''])
	{
		$arr['Error'] = 0;
		try {
			//所有名字包含指定的前缀且第一次出现delimiter字符之间的object作为一组元素
			 $delimiter = '/';
			 //限定返回的object key必须以prefix作为前缀
			 $prefix = $option['pre'];
			 //限定此次返回object的最大数，如果不设定，默认为100，max-keys取值不能大于1000
			 $maxkeys = $option['max'];
			 //设定结果从marker之后按字母排序的第一个开始返回
			 $nextMarker = $option['marker'];
			$options = array(
	            'delimiter' => $delimiter,
	            'prefix' => $prefix,
	            'max-keys' => $maxkeys,
	            'marker' => $nextMarker,
        	);
        	//配置文件
		    $listObjectInfo = $ossClient->listObjects($bucket,$options);
		    //获取文件
		    $objectList = $listObjectInfo->getObjectList();
		    //判断文件是否为空
		    if (!empty($objectList)) {
		        foreach ($objectList as $k=>$objectInfo) {
		        	if($prefix!=''){
		        		$newkey = explode('/',$objectInfo->getKey());
		        		if(empty($newkey[1])) continue;
		        	}
		        	$item = [
		        		'filename'=>$objectInfo->getKey(),
		        		'filesize'=>$objectInfo->getSize(),
		        		'addtime'=>$objectInfo->getLastModified()
		        	];
		        	if($url != '') $item['url'] = $url.$objectInfo->getKey();
		        	$List[] = $item;
		       	}
		    }
		    $nextMarker = $listObjectInfo->getNextMarker();

		} catch (OssException $e) {
		    $arr['Error'] = 1;
			$arr['Message'] = $e->getMessage();
	        return $arr;
		}
		$arr['marker'] = empty($nextMarker) ? 'null'  : $nextMarker;
		$arr['List'] = $List;
		return $arr;
	}

	/**
	 * OSS 文件删除
	 * @param  OSS对象
	 * @param  OSS库名
	 * @param  需要删除的文件名或数组文件名(数组为批量删除)
	 * @return 数组 Error=0 成功 Error=1错误 Message获取错误信息
	 *
	 * $arr = [
				"abc/1.jpg",
				"abc/2.jpg",
		  	];
	 * 调用方法 : $this->deleteFile($ossClient,$setting['bucket'],$arr);
	 */
	public function deleteFile($ossClient,$bucket,$filename)
	{
		$arr['Error'] = 0;
		try {
			if(is_array($filename))
			{
 				$del = $ossClient->deleteObjects($bucket, $filename);
			}else{
		    	$ossClient->deleteObject($bucket, $filename);
			}
			// dump($filename);
		} catch (OssException $e) {
		    $arr['Error'] = 1;
			$arr['Message'] = $e->getMessage();
	        return $arr;
		}
		$arr['Message'] = '删除成功';
		return $arr;
	}

	/**
	 * OSS 判断文件是否存在
	 * @param  OSS对象
	 * @param  OSS库名
	 * @param  需要查询的文件名
	 * @return 数组 Error=0 成功 Error=1错误 Message获取错误信息  Exist > 0 则存在
	 * 
	 * 调用方法 : $this->existFile($ossClient,$setting['bucket'],'完整的文件名.txt');
	 */
	public function existFile($ossClient, $bucket,$filename)
	{
		$arr['Error'] = 0;
	    try{
	        $exist = $ossClient->doesObjectExist($bucket, $filename);
	        if(!$exist) $exist = 0;
	    } catch(OssException $e) {
	        $arr['Error'] = 1;
			$arr['Message'] = $e->getMessage();
	        return $arr;
	    }
	   	$arr['Exist'] = $exist;
		return $arr;
	}
}
