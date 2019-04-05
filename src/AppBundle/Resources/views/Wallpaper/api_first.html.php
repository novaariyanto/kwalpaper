<?php 
function truncate($text, $length=38)
   {
      $trunc = (strlen($text)>$length)?true:false;
      if($trunc)
         return substr($text, 0, $length).'...';
      else
         return $text;
   }
$list=array();

foreach ($wallpapers as $key => $wallpaper) {

	$a["id"]=$wallpaper->getId();
	$a["title"]=$wallpaper->getTitle();
	$a["size"]=$wallpaper->getSize();
	$a["review"]=$wallpaper->getReview();
	$a["downloads"]=$wallpaper->getDownloads();
	$a["resolution"]=$wallpaper->getResolution();
	$a["color"]=$wallpaper->getColor();
	$a["user"]=$wallpaper->getUser()->getName();
	$a["userid"]=$wallpaper->getUser()->getId();
	$a["comment"]=$wallpaper->getComment();
	$a["description"]=$wallpaper->getDescription();
	$a["type"]=$wallpaper->getMedia()->getType();
	$a["extension"]=$wallpaper->getMedia()->getExtension();
	$a["thumbnail"]= $this['imagine']->filter($view['assets']->getUrl($wallpaper->getMedia()->getLink()), 'wallpaper_image');
	$a["image"]= $this['imagine']->filter($view['assets']->getUrl($wallpaper->getMedia()->getLink()), 'wallpaper_thumb');
	$a["wallpaper"] = str_replace("/cache/wallpaper_thumb/uploads/", "/",str_replace("/media/cache/resolve/wallpaper_thumb/", "/", $this['imagine']->filter($view['assets']->getUrl($wallpaper->getMedia()->getLink()) ,'wallpaper_thumb')));
	$a["userimage"]=$wallpaper->getUser()->getImage();
	$a["created"]=$view['time']->diff($wallpaper->getCreated());
	$a["comments"]=sizeof($wallpaper->getComments());
	$list[]=$a;
}
echo json_encode($list, JSON_UNESCAPED_UNICODE);
?>