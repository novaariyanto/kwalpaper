<?php

namespace AppBundle\Controller;
use AppBundle\Entity\Comment;
use AppBundle\Entity\Device;
use AppBundle\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


class HomeController extends Controller
{
 

  function send_notificationToken ($tokens, $message,$key)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $fields = array(
            'registration_ids'  => $tokens,
            'data'   => $message

            );
        $headers = array(
            'Authorization:key = '.$key,
            'Content-Type: application/json'
            );
       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);  
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
       $result = curl_exec($ch);           
       if ($result === FALSE) {
           die('Curl failed: ' . curl_error($ch));
       }
       curl_close($ch);
       return $result;
    }
    function send_notification ($message,$key)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $fields = array(
            'to'  => '/topics/wallpaperhd4K',
            'data'   => $message

            );
        $headers = array(
            'Authorization:key = '.$key,
            'Content-Type: application/json'
            );
       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);  
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
       $result = curl_exec($ch);           
       if ($result === FALSE) {
           die('Curl failed: ' . curl_error($ch));
       }
       curl_close($ch);
       return $result;
    }

   
    public function notifCategoryAction(Request $request)
    {
        
        $imagineCacheManager = $this->get('liip_imagine.cache.manager');
        $em=$this->getDoctrine()->getManager();
        $categories= $em->getRepository("AppBundle:Category")->findAll();
        $defaultData = array();
        $form = $this->createFormBuilder($defaultData)
            ->setMethod('GET')
            ->add('title', TextType::class)
            ->add('message', TextareaType::class)         
            ->add('category', 'entity', array('class' => 'AppBundle:Category'))           
            ->add('icon', UrlType::class,array("label"=>"Large Icon"))
            ->add('image', UrlType::class,array("label"=>"Big Picture"))
            ->add('send', SubmitType::class,array("label"=>"Send notification"))
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // data is an array with "name", "email", and "message" keys
            $data = $form->getData();
            $category_selected = $em->getRepository("AppBundle:Category")->find($data["category"]);
            $message = array(
                        "type"=>"category",
                        "id"=>$category_selected->getId(),
                        "title_category"=>$category_selected->getTitle(),
                        "image_category"=>$imagineCacheManager->getBrowserPath( $category_selected->getMedia()->getLink(), 'section_thumb_api'),
                        "title"=> $data["title"],
                        "message"=>$data["message"],
                        "image"=> $data["image"],
                        "icon"=>$data["icon"]
                        );
            $key=$this->container->getParameter('fire_base_key');
            $message_status = $this->send_notification($message,$key); 
            $this->addFlash('success', 'Operation has been done successfully ');
        }
        return $this->render('AppBundle:Home:notif_category.html.twig',array("form"=>$form->createView()));
    }
   public function notifUrlAction(Request $request)
    {
    
        $imagineCacheManager = $this->get('liip_imagine.cache.manager');
        $em=$this->getDoctrine()->getManager();
        $categories= $em->getRepository("AppBundle:Category")->findAll();
        $defaultData = array();
        $form = $this->createFormBuilder($defaultData)
            ->setMethod('GET')
            ->add('title', TextType::class)
            ->add('message', TextareaType::class)
            ->add('url', UrlType::class,array("label"=>"Url"))
            ->add('icon', UrlType::class,array("label"=>"Large Icon"))
            ->add('image', UrlType::class,array("label"=>"Big Picture"))
            ->add('send', SubmitType::class,array("label"=>"Send notification"))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $message = array(
                        "type"=>"link",
                        "id"=>strlen($data["url"]),
                        "link"=>$data["url"],
                        "title"=> $data["title"],
                        "message"=>$data["message"],
                        "image"=> $data["image"],
                        "icon"=>$data["icon"]
                        );
           $key=$this->container->getParameter('fire_base_key');

          
          $message_status = $this->send_notification($message,$key); 
          $this->addFlash('success', 'Operation has been done successfully ');
            


        }
        return $this->render('AppBundle:Home:notif_url.html.twig',array(
          "form"=>$form->createView()
          ));
    }
    public function notifWallpaperAction(Request $request)
    {
        $imagineCacheManager = $this->get('liip_imagine.cache.manager');
        $em=$this->getDoctrine()->getManager();
        $wallpapers= $em->getRepository("AppBundle:Wallpaper")->findBy(array("enabled"=>true));
        $defaultData = array();
        $form = $this->createFormBuilder($defaultData)
            ->setMethod('GET')
            ->add('title', TextType::class)
            ->add('message', TextareaType::class)
            ->add('wallpaper', 'entity', array('class' => 'AppBundle:Wallpaper'))           
            ->add('icon', UrlType::class,array("label"=>"Large Icon"))
            ->add('image', UrlType::class,array("label"=>"Big Picture"))
            ->add('send', SubmitType::class,array("label"=>"Send notification"))
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // data is an array with "name", "email", and "message" keys
            $data = $form->getData();
            $wallpaper_selected = $em->getRepository("AppBundle:Wallpaper")->find($data["wallpaper"]);
            $message = array(
                        "type"=>"wallpaper",
                        "id"=>$wallpaper_selected->getId(),
                        "wallpaper_title"=>$wallpaper_selected->getTitle(),
                        "wallpaper_size"=>$wallpaper_selected->getSize(),
                        "wallpaper_sets"=>$wallpaper_selected->getSets(),
                        "wallpaper_review"=>$wallpaper_selected->getReview(),
                        "wallpaper_downloads"=>$wallpaper_selected->getDownloads(),
                        "wallpaper_resolution"=>$wallpaper_selected->getResolution(),
                        "wallpaper_color"=>$wallpaper_selected->getColor(),
                        "wallpaper_user"=>$wallpaper_selected->getUser()->getName(),
                        "wallpaper_comment"=>$wallpaper_selected->getComment(),
                        "wallpaper_type"=>$wallpaper_selected->getMedia()->getType(),
                        "wallpaper_description"=>$wallpaper_selected->getDescription(),
                        "wallpaper_extension"=>$wallpaper_selected->getMedia()->getExtension(),
                        "wallpaper_thumbnail"=>$imagineCacheManager->getBrowserPath($wallpaper_selected->getMedia()->getLink(), 'wallpaper_thumb'),
                        "wallpaper_image"=> $imagineCacheManager->getBrowserPath($wallpaper_selected->getMedia()->getLink(), 'wallpaper_image'),
                        "wallpaper_wallpaper" => str_replace("/media/cache/resolve/wallpaper/", "/", $imagineCacheManager->getBrowserPath($wallpaper_selected->getMedia()->getLink(), 'wallpaper')),
                        "wallpaper_userimage"=>$wallpaper_selected->getUser()->getImage(),
                        "wallpaper_created"=>"now",
                        "wallpaper_comments"=>sizeof($wallpaper_selected->getComments()),
                        "title"=> $data["title"],
                        "message"=>$data["message"],
                        "image"=> $data["image"],
                        "icon"=>$data["icon"]
                        );
            $key=$this->container->getParameter('fire_base_key');
            $message_status = $this->send_notification($message,$key); 
            $this->addFlash('success', 'Operation has been done successfully ');
        }
        return $this->render('AppBundle:Home:notif.html.twig',array("form"=>$form->createView()));
    }
    public function notifUserAction(Request $request)
    {
        
        $imagineCacheManager = $this->get('liip_imagine.cache.manager');
        $wallpaper_id= $request->query->get("wallpaper_id");
            $em=$this->getDoctrine()->getManager();


        $defaultData = array();
        $form = $this->createFormBuilder($defaultData)
            ->setMethod('GET')
            ->add('title', TextType::class)
            ->add('wallpaper', HiddenType::class,array("attr"=>array("value"=>$wallpaper_id)))
            ->add('message', TextareaType::class)
            ->add('icon', UrlType::class,array("label"=>"Large Icon"))
            ->add('image', UrlType::class,array("label"=>"Big Picture"))
            ->add('send', SubmitType::class,array("label"=>"Send notification"))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // data is an array with "name", "email", and "message" keys
            $data = $form->getData();
                        $data = $form->getData();
            $wallpaper= $em->getRepository("AppBundle:Wallpaper")->find($data["wallpaper"]);
            $user= $wallpaper->getUser();

            
             if ($user==null) {
                throw new NotFoundHttpException("Page not found");  
            }
            
            $tokens[]=$user->getToken();
        
            $message = array(
                        "type"=>"wallpaper",
                        "id"=>$wallpaper->getId(),
                        "wallpaper_title"=>$wallpaper->getTitle(),
                        "wallpaper_size"=>$wallpaper->getSize(),
                        "wallpaper_sets"=>$wallpaper->getSets(),
                        "wallpaper_review"=>$wallpaper->getReview(),
                        "wallpaper_downloads"=>$wallpaper->getDownloads(),
                        "wallpaper_resolution"=>$wallpaper->getResolution(),
                        "wallpaper_color"=>$wallpaper->getColor(),
                        "wallpaper_user"=>$wallpaper->getUser()->getName(),
                        "wallpaper_comment"=>$wallpaper->getComment(),
                        "wallpaper_type"=>$wallpaper->getMedia()->getType(),
                        "wallpaper_description"=>$wallpaper->getDescription(),

                        "wallpaper_extension"=>$wallpaper->getMedia()->getExtension(),
                        "wallpaper_thumbnail"=>$imagineCacheManager->getBrowserPath($wallpaper->getMedia()->getLink(), 'wallpaper_thumb'),
                        "wallpaper_image"=> $imagineCacheManager->getBrowserPath($wallpaper->getMedia()->getLink(), 'wallpaper_image'),
                        "wallpaper_wallpaper" => str_replace("/media/cache/resolve/wallpaper/", "/", $imagineCacheManager->getBrowserPath($wallpaper->getMedia()->getLink(), 'wallpaper')),
                        "wallpaper_userimage"=>$wallpaper->getUser()->getImage(),
                        "wallpaper_created"=>"now",
                        "wallpaper_comments"=>sizeof($wallpaper->getComments()),
                        "title"=> $data["title"],
                        "message"=>$data["message"],
                        "image"=> $data["image"],
                        "icon"=>$data["icon"]
                        );
            $key=$this->container->getParameter('fire_base_key');

             $message_status = $this->send_notificationToken($tokens, $message,$key); 
            
             $this->addFlash('success', 'Operation has been done successfully ');
            return $this->redirect($this->generateUrl('app_wallpaper_index'));

        }else{
           $wallpaper= $em->getRepository("AppBundle:Wallpaper")->find($wallpaper_id);
        }
        return $this->render('AppBundle:Home:notif_user.html.twig',array(
          "form"=>$form->createView(),
          'wallpaper'=>$wallpaper
        ));
    }
    
    public function indexAction()
    {   
        $em=$this->getDoctrine()->getManager();
        $supports= $em->getRepository("AppBundle:Support")->findAll();
        $supports_count= sizeof($supports);

        $em=$this->getDoctrine()->getManager();
        $devices= $em->getRepository("AppBundle:Device")->findAll();
        $devices_count= sizeof($devices);


        $reports= $em->getRepository("AppBundle:Report")->findAll();
        $reports_count= sizeof($reports);

        $wallpaper= $em->getRepository("AppBundle:Wallpaper")->findAll();
        $wallpaper_count= sizeof($wallpaper);

        $comment= $em->getRepository("AppBundle:Comment")->findAll();
        $comment_count= sizeof($comment);

        $review= $em->getRepository("AppBundle:Wallpaper")->findBy(array("review"=>true));
        $review_count= sizeof($review);




        $category= $em->getRepository("AppBundle:Category")->findAll();
        $category_count= sizeof($category);


        $section= $em->getRepository("AppBundle:Section")->findAll();
        $section_count= sizeof($section);

        $color= $em->getRepository("AppBundle:Color")->findAll();
        $color_count= sizeof($color);

        $slide= $em->getRepository("AppBundle:Slide")->findAll();
        $slide_count= sizeof($slide);

        $users= $em->getRepository("UserBundle:User")->findAll();
        $users_count= sizeof($users)-1;





        return $this->render('AppBundle:Home:index.html.twig',array(
            
                "supports_count"=>$supports_count,
                "devices_count"=>$devices_count,
                "reports_count"=>$reports_count,
                "wallpaper_count"=>$wallpaper_count,
                "category_count"=>$category_count,
                "review_count"=>$review_count,
                "comment_count"=>$comment_count,
                "users_count"=>$users_count,
                "color_count"=>$color_count,
                "section_count"=>$section_count,
                "slide_count"=>$slide_count

        ));
    }
    public function api_deviceAction($tkn,$token){
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $code="200";
        $message="";
        $errors=array();
        $em = $this->getDoctrine()->getManager();
        $d=$em->getRepository('AppBundle:Device')->findOneBy(array("token"=>$tkn));
        if ($d==null) {
            $device = new Device();
            $device->setToken($tkn);
            $em->persist($device);
            $em->flush();
            $message="Deivce added";
        }else{
            $message="Deivce Exist";
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

    



}