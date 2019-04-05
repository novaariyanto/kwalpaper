<?php 
namespace AppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\Wallpaper;
use MediaBundle\Entity\Media;
use AppBundle\Entity\Rate;
use AppBundle\Form\WallpaperMultiType;
use AppBundle\Form\WallpaperType;
use AppBundle\Form\WallpaperEditType;
use AppBundle\Form\WallpaperReviewType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
class WallpaperController extends Controller
{

	  function colorPalette($imageFile, $numColors, $granularity = 5) 
    { 
       $granularity = max(1, abs((int)$granularity)); 
       $colors = array(); 
       $size = @getimagesize($imageFile); 
       if($size === false) 
       { 
          user_error("Unable to get image size data"); 
          return false; 
       } 
       $img = @imagecreatefromstring(file_get_contents($imageFile)); 
       if(!$img) 
       { 
          user_error("Unable to open image file"); 
          return false; 
       } 
       for($x = 0; $x < $size[0]; $x += $granularity) 
       { 
          for($y = 0; $y < $size[1]; $y += $granularity) 
          { 
             $thisColor = imagecolorat($img, $x, $y); 
             $rgb = imagecolorsforindex($img, $thisColor); 
             $red = round(round(($rgb['red'] / 0x33)) * 0x33); 
             $green = round(round(($rgb['green'] / 0x33)) * 0x33); 
             $blue = round(round(($rgb['blue'] / 0x33)) * 0x33); 
             $thisRGB = sprintf('%02X%02X%02X', $red, $green, $blue); 
             if(array_key_exists($thisRGB, $colors)) 
             { 
                $colors[$thisRGB]++; 
             } 
             else 
             { 
                $colors[$thisRGB] = 1; 
             } 
          } 
       } 
       arsort($colors); 
       return array_slice(array_keys($colors), 0, $numColors); 
    } 
    function mixcolors($color1, $color2)
    {
      $c1_p1 = hexdec(substr($color1, 0, 2));
      $c1_p2 = hexdec(substr($color1, 2, 2));
      $c1_p3 = hexdec(substr($color1, 4, 2));

      $c2_p1 = hexdec(substr($color2, 0, 2));
      $c2_p2 = hexdec(substr($color2, 2, 2));
      $c2_p3 = hexdec(substr($color2, 4, 2));

      $m_p1 = sprintf('%02x', (round(($c1_p1 + $c2_p1)/2)));
      $m_p2 = sprintf('%02x', (round(($c1_p2 + $c2_p2)/2)));
      $m_p3 = sprintf('%02x', (round(($c1_p3 + $c2_p3)/2)));

     return    $m_p1 . $m_p2 . $m_p3;
    }
    public function getMainColor($url){
        $palette = $this->colorPalette($url, 7);
        if ($palette == null) {
          return "#0c2635";
        }
        if (sizeof($palette) == 7) {
            return $this->mixcolors($this->mixcolors($this->mixcolors($this->mixcolors($this->mixcolors($this->mixcolors($palette[0],$palette[1]),$palette[2]),$palette[3]),$palette[4]),$palette[5]),$palette[6]);
        }else if (sizeof($palette) == 6){
            return $this->mixcolors($this->mixcolors($this->mixcolors($this->mixcolors($this->mixcolors($palette[0],$palette[1]),$palette[2]),$palette[3]),$palette[4]),$palette[5]);
        }else if (sizeof($palette) == 5){
            return $this->mixcolors($this->mixcolors($this->mixcolors($this->mixcolors($palette[0],$palette[1]),$palette[2]),$palette[3]),$palette[4]);
        }else if (sizeof($palette) == 4){
            return $this->mixcolors($this->mixcolors($this->mixcolors($palette[0],$palette[1]),$palette[2]),$palette[3]);
        }else if (sizeof($palette) == 3){
            return $this->mixcolors($this->mixcolors($palette[0],$palette[1]),$palette[2]);
        }else if (sizeof($palette) == 2){
            return $this->mixcolors($palette[0],$palette[1]);
        }else if (sizeof($palette) == 1){
            return $palette[0];
        }else if (sizeof($palette) == 0){
            return "#0c2635";
        }
    }
  	public function format_size($size) {
  	  if ($size < 1000) {
  	    return $size . ' B';
  	  }
  	  else {
  	    $size = $size / 1000;
  	    $units = ['KB', 'MB', 'GB', 'TB'];
  	    foreach ($units as $unit) {
  	      if (round($size,2) >= 1000) {
  	        $size = $size / 1000;
  	      }
  	      else {
  	        break;
  	      }
  	    }
  	    return round($size, 2) . ' ' . $unit;
  	  }
  	}
  	public function cssifysize($img) { 
  	    $dimensions = getimagesize($img); 
  	    return $dimensions[0]." X ".$dimensions[1]; 
  	} 
    public function addAction(Request $request)
    {
        $wallpaper= new Wallpaper();
        $form = $this->createForm(new WallpaperType(),$wallpaper);
        $em=$this->getDoctrine()->getManager();
        $colors=$em->getRepository('AppBundle:Color')->findBy(array(),array("position"=>"asc"));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
        	 if( $wallpaper->getFile()!=null ){
                $file = $wallpaper->getFile();
                $media= new Media();
                $resolution= $this->cssifysize($file);
                $media->setFile($file);
                $media->upload($this->container->getParameter('files_directory'));
                $em->persist($media);
                $em->flush();
                $size = $this->format_size($file->getClientSize());
                $url  = $this->container->getParameter('files_directory').$media->getUrl();
  		          $color= $this->getMainColor($url);
                $wallpaper->setUser($this->getUser());
          			$wallpaper->setDownloads(0);
          			$wallpaper->setSets(0);
        				$wallpaper->setColor("#".$color);
        				$wallpaper->setResolution($resolution);
                $wallpaper->setSize($size);
        				$wallpaper->setMedia($media);
  	            $em->persist($wallpaper);
  	            $em->flush();
                      
              
                $this->addFlash('success', 'Operation has been done successfully');
                return $this->redirect($this->generateUrl('app_wallpaper_index'));
            }else{
                $error = new FormError("Required image file");
                $form->get('file')->addError($error);
            }
 		     }
        return $this->render("AppBundle:Wallpaper:add.html.twig",array("form"=>$form->createView(),"colors"=>$colors));
    }
    public function indexAction(Request $request)
    {

    	$em=$this->getDoctrine()->getManager();
	     $q="  ";
        if ($request->query->has("q") and $request->query->get("q")!="") {
           $q.=" AND  w.title like '%".$request->query->get("q")."%'";
        }
        $dql        = "SELECT w FROM AppBundle:Wallpaper w  WHERE w.review = false ".$q ." ORDER BY w.created desc ";
        $query      = $em->createQuery($dql);
        $paginator  = $this->get('knp_paginator');
        $wallpapers = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
            12
        );
        $wallpapers_list=$em->getRepository('AppBundle:Wallpaper')->findAll();
	      $wallpapers_count= sizeof($wallpapers_list);
	      return $this->render('AppBundle:Wallpaper:index.html.twig',array("wallpapers"=>$wallpapers,"wallpapers_count"=>$wallpapers_count));    
	  }
    public function reviewsAction(Request $request)
    {

      $em=$this->getDoctrine()->getManager();
      
        $dql        = "SELECT w FROM AppBundle:Wallpaper w  WHERE w.review = true ORDER BY w.created desc ";
        $query      = $em->createQuery($dql);
        $paginator  = $this->get('knp_paginator');
        $wallpapers = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
            12
        );
        $wallpapers_list=$em->getRepository('AppBundle:Wallpaper')->findBy(array("review"=>true));
        $wallpapers_count= sizeof($wallpapers_list);
        return $this->render('AppBundle:Wallpaper:reviews.html.twig',array("wallpapers"=>$wallpapers,"wallpapers_count"=>$wallpapers_count));    
    }
    public function deleteAction($id,Request $request){
        $em=$this->getDoctrine()->getManager();
        $wallpaper = $em->getRepository("AppBundle:Wallpaper")->find($id);
        if($wallpaper==null){
            throw new NotFoundHttpException("Page not found");
        }

        $form=$this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->add('Yes', 'submit')
            ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $report = $em->getRepository("AppBundle:Report")->findOneBy(array("wallpaper"=>$wallpaper));
            if ($report!=null) {
                $em->remove($report);
                $em->flush();
            }
            $slide = $em->getRepository("AppBundle:Slide")->findOneBy(array("wallpaper"=>$wallpaper));
            if ($slide!=null) {
                $slide->setWallpaper(null);
                $em->flush();
            }
	        	if( $wallpaper->getMedia()!=null ){
	                $media_old=$wallpaper->getMedia();
	                $media_old->delete($this->container->getParameter('files_directory'));
	                $em->remove($wallpaper);
                  $em->flush();
	          }

            $em->remove($media_old);
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirect($this->generateUrl('app_wallpaper_index'));
        }
        return $this->render('AppBundle:Wallpaper:delete.html.twig',array("form"=>$form->createView()));
    }
    
    public function editAction(Request $request,$id)
    {
        $em=$this->getDoctrine()->getManager();
        $wallpaper=$em->getRepository("AppBundle:Wallpaper")->findOneBy(array("id"=>$id,"review"=>false));
        if ($wallpaper==null) {
            throw new NotFoundHttpException("Page not found");
        }
        $form = $this->createForm(new WallpaperEditType(),$wallpaper);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
        	if( $wallpaper->getFile()!=null ){
                $media= new Media();
                $media_old=$wallpaper->getMedia();
                $media->setFile($wallpaper->getFile());
                $media->setEnabled(true);
                $media->upload($this->container->getParameter('files_directory'));
                $em->persist($media);
                $em->flush();
                $wallpaper->setMedia($media);
                $em->flush();
                $media_old->delete($this->container->getParameter('files_directory'));
                $em->remove($media_old);
                $em->flush();
            }
            $wallpaper->setColor("#". $wallpaper->getColor());
            $em->persist($wallpaper);
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirect($this->generateUrl('app_wallpaper_index'));
        }
        $colors=$em->getRepository('AppBundle:Color')->findBy(array(),array("position"=>"asc"));
        return $this->render("AppBundle:Wallpaper:edit.html.twig",array("colors"=>$colors,"form"=>$form->createView()));
    }
    public function reviewAction(Request $request,$id)
    {
        $em=$this->getDoctrine()->getManager();
        $wallpaper=$em->getRepository("AppBundle:Wallpaper")->findOneBy(array("id"=>$id,"review"=>true));
        if ($wallpaper==null) {
            throw new NotFoundHttpException("Page not found");
        }
        $form = $this->createForm(new WallpaperReviewType(),$wallpaper);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $wallpaper->setReview(false);
            $wallpaper->setEnabled(true);
            $wallpaper->setColor("#".$wallpaper->getColor());
            $wallpaper->setCreated(new \DateTime());
            $em->persist($wallpaper);
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirect($this->generateUrl('app_home_notif_user',array("wallpaper_id"=>$wallpaper->getId())));
        }
        $colors=$em->getRepository('AppBundle:Color')->findBy(array(),array("position"=>"asc"));
        return $this->render("AppBundle:Wallpaper:review.html.twig",array("colors"=>$colors,"form"=>$form->createView()));
    }
     public function viewAction(Request $request,$id)
    {
        $em=$this->getDoctrine()->getManager();
        $wallpaper=$em->getRepository("AppBundle:Wallpaper")->find($id);
        if ($wallpaper==null) {
            throw new NotFoundHttpException("Page not found");
        }

        $rates_1 = $em->getRepository('AppBundle:Rate')->findBy(array("wallpaper"=>$wallpaper,"value"=>1));
        $rates_2 = $em->getRepository('AppBundle:Rate')->findBy(array("wallpaper"=>$wallpaper,"value"=>2));
        $rates_3 = $em->getRepository('AppBundle:Rate')->findBy(array("wallpaper"=>$wallpaper,"value"=>3));
        $rates_4 = $em->getRepository('AppBundle:Rate')->findBy(array("wallpaper"=>$wallpaper,"value"=>4));
        $rates_5 = $em->getRepository('AppBundle:Rate')->findBy(array("wallpaper"=>$wallpaper,"value"=>5));
        $rates = $em->getRepository('AppBundle:Rate')->findBy(array("wallpaper"=>$wallpaper));


        $ratings["rate_1"]=sizeof($rates_1);
        $ratings["rate_2"]=sizeof($rates_2);
        $ratings["rate_3"]=sizeof($rates_3);
        $ratings["rate_4"]=sizeof($rates_4);
        $ratings["rate_5"]=sizeof($rates_5);


        $t = sizeof($rates_1) + sizeof($rates_2) +sizeof($rates_3)+ sizeof($rates_4) + sizeof($rates_5);
        if ($t == 0) {
            $t=1;
        }
        $values["rate_1"]=(sizeof($rates_1)*100)/$t;
        $values["rate_2"]=(sizeof($rates_2)*100)/$t;
        $values["rate_3"]=(sizeof($rates_3)*100)/$t;
        $values["rate_4"]=(sizeof($rates_4)*100)/$t;
        $values["rate_5"]=(sizeof($rates_5)*100)/$t;

        $total=0;
        $count=0;
        foreach ($rates as $key => $r) {
           $total+=$r->getValue();
           $count++;
        }
        $v=0;
        if ($count != 0) {
            $v=$total/$count;
        }
        $rating=$v;
        return $this->render("AppBundle:Wallpaper:view.html.twig",array("wallpaper"=>$wallpaper,"rating"=>$rating,"ratings"=>$ratings,"values"=>$values));
    }
    public function api_allAction(Request $request,$page,$order,$token){
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $nombre=30;
        $em=$this->getDoctrine()->getManager();
        $imagineCacheManager = $this->get('liip_imagine.cache.manager');
        $repository = $em->getRepository('AppBundle:Wallpaper');
        $query = $repository->createQueryBuilder('w')
        ->where("w.enabled = true")
        ->addOrderBy('w.'.$order, 'DESC')
        ->addOrderBy('w.id', 'asc')
        ->setFirstResult($nombre*$page)
        ->setMaxResults($nombre)
        ->getQuery();
        $wallpapers = $query->getResult();
        return $this->render('AppBundle:Wallpaper:api_first.html.php',array("wallpapers"=>$wallpapers));
    }
    public function api_by_categoryAction(Request $request,$page,$category,$token){
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $nombre=30;
        $em=$this->getDoctrine()->getManager();
        $imagineCacheManager = $this->get('liip_imagine.cache.manager');
        $repository = $em->getRepository('AppBundle:Wallpaper');
        
        $query = $repository->createQueryBuilder('w')
          ->leftJoin('w.categories', 'c')
          ->where('c.id = :category',"w.enabled = true")
          ->setParameter('category', $category)
          ->addOrderBy('w.created', 'DESC')
          ->addOrderBy('w.id', 'asc')
          ->setFirstResult($nombre*$page)
          ->setMaxResults($nombre)
          ->getQuery();
        
        $wallpapers = $query->getResult();
        return $this->render('AppBundle:Wallpaper:api_first.html.php',array("wallpapers"=>$wallpapers));
    }
    public function api_by_userAction(Request $request,$page,$user,$token){
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $nombre=30;
        $em=$this->getDoctrine()->getManager();
        $imagineCacheManager = $this->get('liip_imagine.cache.manager');
        $repository = $em->getRepository('AppBundle:Wallpaper');
        
        $query = $repository->createQueryBuilder('w')
          ->where('w.user = :user',"w.enabled = true")
          ->setParameter('user', $user)
          ->addOrderBy('w.created', 'DESC')
          ->addOrderBy('w.id', 'asc')
          ->setFirstResult($nombre*$page)
          ->setMaxResults($nombre)
          ->getQuery();
        $wallpapers = $query->getResult();
        return $this->render('AppBundle:Wallpaper:api_first.html.php',array("wallpapers"=>$wallpapers));
    }
    public function api_by_meAction(Request $request,$page,$user,$token){
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $nombre=30;
        $em=$this->getDoctrine()->getManager();
        $imagineCacheManager = $this->get('liip_imagine.cache.manager');
        $repository = $em->getRepository('AppBundle:Wallpaper');
        
        $query = $repository->createQueryBuilder('w')
          ->where('w.user = :user')
          ->setParameter('user', $user)
          ->addOrderBy('w.created', 'DESC')
          ->addOrderBy('w.id', 'asc')
          ->setFirstResult($nombre*$page)
          ->setMaxResults($nombre)
          ->getQuery();
        $wallpapers = $query->getResult();
        return $this->render('AppBundle:Wallpaper:api_first.html.php',array("wallpapers"=>$wallpapers));
    }
    public function api_by_colorAction(Request $request,$page,$color,$token){
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $nombre=30;
        $em=$this->getDoctrine()->getManager();
        $imagineCacheManager = $this->get('liip_imagine.cache.manager');
        $repository = $em->getRepository('AppBundle:Wallpaper');
        
        $query = $repository->createQueryBuilder('w')
          ->leftJoin('w.colors', 'c')
          ->where('c.id = :color',"w.enabled = true")
          ->setParameter('color', $color)
          ->addOrderBy('w.created', 'DESC')
          ->addOrderBy('w.id', 'asc')
          ->setFirstResult($nombre*$page)
          ->setMaxResults($nombre)
          ->getQuery();
        
        $wallpapers = $query->getResult();
        return $this->render('AppBundle:Wallpaper:api_first.html.php',array("wallpapers"=>$wallpapers));
    }
    public function api_by_queryAction(Request $request,$page,$query,$token){
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $nombre=30;
        $em=$this->getDoctrine()->getManager();
        $imagineCacheManager = $this->get('liip_imagine.cache.manager');
        $repository = $em->getRepository('AppBundle:Wallpaper');
        
        $query_dql = $repository->createQueryBuilder('w')
          ->where("w.enabled = true","LOWER(w.title) like LOWER('%".$query."%') OR LOWER(w.tags) like LOWER('%".$query."%') ")
          ->addOrderBy('w.created', 'DESC')
          ->addOrderBy('w.id', 'asc')
          ->setFirstResult($nombre*$page)
          ->setMaxResults($nombre)
          ->getQuery();
        
        $wallpapers = $query_dql->getResult();
        return $this->render('AppBundle:Wallpaper:api_first.html.php',array("wallpapers"=>$wallpapers));
    }
    public function api_add_downloadAction(Request $request,$token){
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $id = $request->get("id");        
        $em=$this->getDoctrine()->getManager();
        $wallpaper=$em->getRepository("AppBundle:Wallpaper")->find($id);
        $wallpaper->setDownloads($wallpaper->getDownloads()+1);
        $em->flush();
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent=$serializer->serialize($wallpaper->getDownloads(), 'json');
        return new Response($jsonContent);
    }
    public function api_add_setAction(Request $request,$token){
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $id = $request->get("id");        
        $em=$this->getDoctrine()->getManager();
        $wallpaper=$em->getRepository("AppBundle:Wallpaper")->find($id);
        $wallpaper->setSets($wallpaper->getSets()+1);
        $em->flush();
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent=$serializer->serialize($wallpaper->getDownloads(), 'json');
        return new Response($jsonContent);
    }


    public function api_add_rateAction($user,$wallpaper,$value,$token)
    {
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $em=$this->getDoctrine()->getManager();
        $a=$em->getRepository('AppBundle:Wallpaper')->find($wallpaper);
        $u=$em->getRepository("UserBundle:User")->find($user);
        $code="200";
        $message="";
        $errors=array();

        if ($u!=null and $a!=null) {

            $rate =  $em->getRepository('AppBundle:Rate')->findOneBy(array("user"=>$u,"wallpaper"=>$a));
            if ($rate == null) {
                $rate_obj = new Rate();
                $rate_obj->setValue($value);
                $rate_obj->setWallpaper($a);
                $rate_obj->setUser($u);
                $em->persist($rate_obj);
                $em->flush();
                $message="Your Ratting has been added";

            }else{
                $rate->setValue($value);
                $em->flush();
                $message="Your Ratting has been edit";

            }
        }else{
            $code="500";
            $message="Sorry, your rate could not be added at this time";
        }
        $error=array(
            "code"=>$code,
            "message"=>$message,
            "values"=>$errors
        );
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent=$serializer->serialize($error, 'json');
        return new Response($jsonContent);
    }
    public function api_get_rateAction($user=null,$wallpaper,$token)
    {
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $em=$this->getDoctrine()->getManager();
        $a=$em->getRepository('AppBundle:Wallpaper')->find($wallpaper);
        $u=$em->getRepository("UserBundle:User")->find($user);
        $code="200";
        $message="";
        $errors=array();

        if ($a!=null) {
            $rates_1 = $em->getRepository('AppBundle:Rate')->findBy(array("wallpaper"=>$a,"value"=>1));
            $rates_2 = $em->getRepository('AppBundle:Rate')->findBy(array("wallpaper"=>$a,"value"=>2));
            $rates_3 = $em->getRepository('AppBundle:Rate')->findBy(array("wallpaper"=>$a,"value"=>3));
            $rates_4 = $em->getRepository('AppBundle:Rate')->findBy(array("wallpaper"=>$a,"value"=>4));
            $rates_5 = $em->getRepository('AppBundle:Rate')->findBy(array("wallpaper"=>$a,"value"=>5));
            $rates = $em->getRepository('AppBundle:Rate')->findBy(array("wallpaper"=>$a));
            $rate = null;
            if ($u!=null) {
                $rate =  $em->getRepository('AppBundle:Rate')->findOneBy(array("user"=>$u,"wallpaper"=>$a));
            }
            if ($rate == null) {
                $code="202";
            }else{
                $message= $rate->getValue();
            }

            $errors[]=array("name"=>"1","value"=>sizeof($rates_1));
            $errors[]=array("name"=>"2","value"=>sizeof($rates_2));
            $errors[]=array("name"=>"3","value"=>sizeof($rates_3));
            $errors[]=array("name"=>"4","value"=>sizeof($rates_4));
            $errors[]=array("name"=>"5","value"=>sizeof($rates_5));
            $total=0;
            $count=0;
            foreach ($rates as $key => $r) {
               $total+=$r->getValue();
               $count++;
            }
            $v=0;
            if ($count != 0) {
                $v=$total/$count;
            }
            $v2=number_format((float)$v, 1, '.', '');
            $errors[]=array("name"=>"rate","value"=>$v2);



        }else{
            $code="500";
            $message="Sorry, your rate could not be added at this time";
        }
        $error=array(
            "code"=>$code,
            "message"=>$message,
            "values"=>$errors
        );
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent=$serializer->serialize($error, 'json');
        return new Response($jsonContent);
    }

    public function api_uploadAction(Request $request,$token)
    {

        $id=str_replace('"', '', $request->get("id"));
        $key=str_replace('"', '', $request->get("key"));
        $title=str_replace('"', '', $request->get("title"));

        $colors_ids=str_replace('"', '',$request->get("colors"));
        $colors_list = split( "_",$colors_ids);

        $categories_ids=str_replace('"', '',$request->get("categories"));
        $categories_list = split( "_",$categories_ids);

        $code="200";
        $message="Ok";
        $values=array();
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $em=$this->getDoctrine()->getManager();
        $user=$em->getRepository('UserBundle:User')->findOneBy(array("id"=>$id));  
        if ($user==null) {
            throw new NotFoundHttpException("Page not found");
        }
        if (sha1($user->getPassword()) != $key) {
           throw new NotFoundHttpException("Page not found");
        }   
        if ($user) {     

            if ($this->getRequest()->files->has('uploaded_file')) {
                // $old_media=$user->getMedia();
                $file = $this->getRequest()->files->get('uploaded_file');


                $media= new Media();
                $resolution= $this->cssifysize($file);
                $media->setFile($file);
                $media->upload($this->container->getParameter('files_directory'));
                $em->persist($media);
                $em->flush();
                $size = $this->format_size($file->getClientSize());
                $url  = $this->container->getParameter('files_directory').$media->getUrl();
                $color= $this->getMainColor($url);
                $w= new Wallpaper();
                $w->setDownloads(0);

                foreach ($colors_list as $key => $id_color) {
                  $color_obj = $em->getRepository('AppBundle:Color')->find($id_color); 
                  if ($color_obj) {
                    $w->addColor($color_obj);
                  }
                }
                foreach ($categories_list as $key => $id_category) {
                  $category_obj = $em->getRepository('AppBundle:Category')->find($id_category); 
                  if ($category_obj) {
                      $w->addCategory($category_obj);
                  }
                }
                $w->setComment(true);
                $w->setEnabled(false);
                $w->setReview(true);
                $w->setTitle($title);
                $w->setUser($user);
                $w->setSets(0);
                $w->setColor("#".$color);
                $w->setResolution($resolution);
                $w->setSize($size);
                $w->setMedia($media);
                $em->persist($w);
                $em->flush();








               /* $user->setMedia($media);
                if($old_media!=null){
                        $old_media->remove($this->container->getParameter('files_directory'));
                        $em->remove($old_media);
                        $em->flush();
                }
                $em->flush();   
                $imagineCacheManager = $this->get('liip_imagine.cache.manager');
                $values[]=array("name"=>"url","value"=>$imagineCacheManager->getBrowserPath($media->getLink(), 'profile_picture'));
                */
            }
        }
        $error=array(
            "code"=>$code,
            "message"=>$message,
            "values"=>$values
            );
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent=$serializer->serialize($error, 'json');
        return new Response($jsonContent);
    }
    public function multiAction(Request $request)
    {
        $wallpaper= new Wallpaper();
        $form = $this->createForm(new WallpaperMultiType(),$wallpaper);
        $em=$this->getDoctrine()->getManager();
        $colors=$em->getRepository('AppBundle:Color')->findBy(array(),array("position"=>"asc"));
        $wallpaper->setTitle("new wallpaper");
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
           if( $wallpaper->getFiles()!=null ){
            $all=0;
                $valide=0;
                $pos=1;
                foreach($wallpaper->getFiles() as $k => $file){
                    $type= $file->getMimeType(); 
                    $all++;
                    if ($type=="image/jpeg" or $type=="image/png") {  
                        $media= new Media();
                        $resolution= $this->cssifysize($file);
                        $media->setFile($file);
                        $media->upload($this->container->getParameter('files_directory'));
                        $em->persist($media);
                        $em->flush();
                        $size = $this->format_size($file->getClientSize());
                        $url  = $this->container->getParameter('files_directory').$media->getUrl();
                        $color= $this->getMainColor($url);
                        $w= new Wallpaper();
                        $w->setDownloads(0);
                        $w->setCategories($wallpaper->getCategories());
                        $w->setTags($wallpaper->getTags());
                        $w->setDescription($wallpaper->getDescription());
                        $w->setColors($wallpaper->getColors());
                        $w->setComment($wallpaper->getComment());
                        $w->setEnabled($wallpaper->getEnabled());
                        if ($wallpaper->getUsefilename()) {
                          $w->setTitle(preg_replace('/\\.[^.\\s]{3,4}$/', '', $file->getClientOriginalName()));
                        }else{
                          $w->setTitle($wallpaper->getTitlemulti());
                        }
                        $w->setUser($this->getUser());
                        $w->setSets(0);
                        $w->setColor("#".$color);
                        $w->setResolution($resolution);
                        $w->setSize($size);
                        $w->setMedia($media);
                        $em->persist($w);
                        $em->flush();
                        $valide++;
                        $pos++;
                        //$audio->setMedia($media);
                      }
                }  
                $this->addFlash('success', 'Operation has been done successfully, Image uploaded '.$valide."/".$all);
                return $this->redirect($this->generateUrl('app_wallpaper_index'));
            }else{
              $error = new FormError("Required image file");
              $form->get('files')->addError($error);
         }
    }
        return $this->render("AppBundle:Wallpaper:multi.html.twig",array("form"=>$form->createView(),"colors"=>$colors));
    }
}
?>