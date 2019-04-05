<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\Comment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends Controller
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
    public function indexAction(Request $request)
    {
        $em= $this->getDoctrine()->getManager();
        $dql        = "SELECT c FROM AppBundle:Comment c  ORDER BY c.created desc";
        $query      = $em->createQuery($dql);
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
            10
        );
        $comments=$em->getRepository('AppBundle:Comment')->findAll();
        
        return $this->render('AppBundle:Comment:index.html.twig',
            array(
                'pagination' => $pagination,
                'comments' => $comments,
            )
        );
    }
    public function api_listAction($id,$token)
    {
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $em=$this->getDoctrine()->getManager();
        $wallpaper=$em->getRepository('AppBundle:Wallpaper')->find($id);
        $comments=array();
        if ($wallpaper!=null) {
            $comments=$em->getRepository('AppBundle:Comment')->findBy(array("wallpaper"=>$wallpaper));
        }
        return $this->render('AppBundle:Comment:api_by.html.php',
            array('comments' => $comments)
        );  
    }
    public function api_addAction(Request $request,$token)
    {
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }

        $user=$request->get("user");
        $wallpaper=$request->get("id");
        $comment=$request->get("comment");

        $em=$this->getDoctrine()->getManager();
        $a=$em->getRepository('AppBundle:Wallpaper')->find($wallpaper);
        $u=$em->getRepository("UserBundle:User")->find($user);
        $code="200";
        $message="";
        $errors=array();
        if ($a) {

            $imagineCacheManager = $this->get('liip_imagine.cache.manager');


            if ($u->getId()!=$a->getUser()->getId()) {
 
            $tokens[]=$a->getUser()->getToken();
            $message = array(
                        "type"=>"wallpaper",
                        "id"=>$a->getId(),
                        "wallpaper_title"=>$a->getTitle(),
                        "wallpaper_size"=>$a->getSize(),
                        "wallpaper_sets"=>$a->getSets(),
                        "wallpaper_review"=>$a->getReview(),
                        "wallpaper_downloads"=>$a->getDownloads(),
                        "wallpaper_resolution"=>$a->getResolution(),
                        "wallpaper_color"=>$a->getColor(),
                        "wallpaper_user"=>$a->getUser()->getName(),
                        "wallpaper_comment"=>$a->getComment(),
                        "wallpaper_type"=>$a->getMedia()->getType(),
                        "wallpaper_description"=>$a->getDescription(),
                        "wallpaper_extension"=>$a->getMedia()->getExtension(),
                        "wallpaper_thumbnail"=>$imagineCacheManager->getBrowserPath($a->getMedia()->getLink(), 'wallpaper_thumb'),
                        "wallpaper_image"=> $imagineCacheManager->getBrowserPath($a->getMedia()->getLink(), 'wallpaper_image'),
                        "wallpaper_wallpaper" => str_replace("/media/cache/resolve/wallpaper/", "/", $imagineCacheManager->getBrowserPath($a->getMedia()->getLink(), 'wallpaper')),
                        "wallpaper_userimage"=>$a->getUser()->getImage(),
                        "wallpaper_created"=>"now",
                        "wallpaper_comments"=>sizeof($a->getComments()),
                        "title"=>"New comment has been added to your wallpaper",
                        "message"=>"New comment has been added to your wallpaper",
                        "image"=> $imagineCacheManager->getBrowserPath($a->getMedia()->getLink(), 'wallpaper_image'),
                        "icon"=>$imagineCacheManager->getBrowserPath($a->getMedia()->getLink(), 'wallpaper_image')
                        );
            $key=$this->container->getParameter('fire_base_key');

            $this->send_notificationToken($tokens, $message,$key); 
            }

            $comment_obj = new Comment();
            $comment_obj->setContent($comment);
            $comment_obj->setEnabled(true);
            $comment_obj->setWallpaper($a);
            $comment_obj->setUser($u);

            $em->persist($comment_obj);
            $em->flush();  
            $errors[]=array("name"=>"id","value"=>$comment_obj->getId());
            $errors[]=array("name"=>"content","value"=>$comment_obj->getContent());
            $errors[]=array("name"=>"user","value"=>$comment_obj->getUser()->getName());
            $errors[]=array("name"=>"image","value"=>$comment_obj->getUser()->getImage());
            $errors[]=array("name"=>"enabled","value"=>$comment_obj->getEnabled());
            $errors[]=array("name"=>"created","value"=>"now");
            $message="Your comment has been added";
            
            



        }else{
            $code="510";
            $message="Sorry, your comment could not be added at this time".$wallpaper;
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
    public function hideAction($id,Request $request){
        $em=$this->getDoctrine()->getManager();
        $comment = $em->getRepository("AppBundle:Comment")->find($id);
        if($comment==null){
            throw new NotFoundHttpException("Page not found");
        }
    	$wallpaper=$comment->getWallpaper();
        $user=$comment->getUser();
    	if ($comment->getEnabled()==true) {
    		$comment->setEnabled(false);
    	}else{
    		$comment->setEnabled(true);
    	}
        $em->flush();
        $this->addFlash('success', 'Operation has been done successfully');
        return  $this->redirect($request->server->get('HTTP_REFERER'));
    }
    public function hide_twoAction($id,Request $request){
        $em=$this->getDoctrine()->getManager();

        $comment = $em->getRepository("AppBundle:Comment")->find($id);
        if($comment==null){
            throw new NotFoundHttpException("Page not found");
        }
        	$id_article=$comment->getWallpaper()->getId();
        	if ($comment->getEnabled()==true) {
        		$comment->setEnabled(false);
        	}else{
        		$comment->setEnabled(true);
        	}
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirect($this->generateUrl('app_comment_index'));

    }

    public function deleteAction($id,Request $request){
        $em=$this->getDoctrine()->getManager();

        $comment = $em->getRepository("AppBundle:Comment")->find($id);
        if($comment==null){
            throw new NotFoundHttpException("Page not found");
        }
        $wallpaper=$comment->getWallpaper();
        $user=$comment->getUser();
        $form=$this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->add('Yes', 'submit')
            ->getForm();
        if ($request->query->has("user")) {
            $url = $this->generateUrl('user_user_edit',array("id"=>$user->getId()));
        }
        if ($request->query->has("wallpaper")) {
            $url = $this->generateUrl('app_wallpaper_view',array("id"=>$wallpaper->getId()));
        }
        if ($request->query->has("comment")) {
            $url = $this->generateUrl('app_comment_index');
        }
        
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirect($url);

        }
        return $this->render('AppBundle:Comment:delete.html.twig',array("url"=>$url,"form"=>$form->createView()));
    }
}
