<?php 
$list=array();
foreach ($slides as $key => $slide) {
	$s=null;
    $s["id"]=$slide->getId();
    $s["title"]=$slide->getTitle();
    $s["type"]=$slide->getType();
    $s["image"]=$this['imagine']->filter($view['assets']->getUrl($slide->getMedia()->getLink()), 'slide_thumb');
    if ($slide->getType()==3 && $slide->getWallpaper()!=null)
    {
		$a["id"]=$slide->getWallpaper()->getId();
		$a["title"]=$slide->getWallpaper()->getTitle();
		$a["size"]=$slide->getWallpaper()->getSize();
		$a["review"]=$slide->getWallpaper()->getReview();
		$a["downloads"]=$slide->getWallpaper()->getDownloads();
		$a["resolution"]=$slide->getWallpaper()->getResolution();
		$a["color"]=$slide->getWallpaper()->getColor();
		$a["user"]=$slide->getWallpaper()->getUser()->getName();
		$a["userid"]=$slide->getWallpaper()->getUser()->getId();
		$a["description"]=$slide->getWallpaper()->getDescription();

		$a["comment"]=$slide->getWallpaper()->getComment();
		$a["type"]=$slide->getWallpaper()->getMedia()->getType();
		$a["extension"]=$slide->getWallpaper()->getMedia()->getExtension();
		$a["thumbnail"]= $this['imagine']->filter($view['assets']->getUrl($slide->getWallpaper()->getMedia()->getLink()), 'wallpaper_image');
		$a["image"]= $this['imagine']->filter($view['assets']->getUrl($slide->getWallpaper()->getMedia()->getLink()), 'wallpaper_thumb');
		$a["wallpaper"] = str_replace("/media/cache/resolve/wallpaper_thumb/", "/", $this['imagine']->filter($view['assets']->getUrl($slide->getWallpaper()->getMedia()->getLink()) ,'wallpaper_thumb'));
		$a["userimage"]=$slide->getWallpaper()->getUser()->getImage();
		$a["created"]=$view['time']->diff($slide->getWallpaper()->getCreated());
		$a["comments"]=sizeof($slide->getWallpaper()->getComments());
		$s["wallpaper"]=$a;
    }elseif($slide->getType()==1 && $slide->getCategory()!=null){
		$c["id"]=$slide->getCategory()->getId();
        $c["title"]=$slide->getCategory()->getTitle();
        $c["image"]=$this['imagine']->filter($view['assets']->getUrl($slide->getCategory()->getMedia()->getLink()), 'section_thumb_api');
		$s["category"]=$c;
	}elseif($slide->getType()==2 && $slide->getUrl()!=null){
	    $s["url"]=$slide->getUrl();
    }
    $list[]=$s;
}
echo json_encode($list, JSON_UNESCAPED_UNICODE);

 ?>