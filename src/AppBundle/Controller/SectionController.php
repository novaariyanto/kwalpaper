<?php 
namespace AppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\Section;
use MediaBundle\Entity\Media;
use AppBundle\Form\SectionType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
class SectionController extends Controller
{

    public function api_listAction(Request $request,$token)
    {
        if ($token!=$this->container->getParameter('token_app')) {
            throw new NotFoundHttpException("Page not found");  
        }
        $em=$this->getDoctrine()->getManager();
        $imagineCacheManager = $this->get('liip_imagine.cache.manager');
        $sections=$em->getRepository("AppBundle:Section")->findBy(array(),array("position"=>"asc"));
        $list=array();
        foreach ($sections as $key => $section) {
            $s["id"]=$section->getId();
            $s["title"]=$section->getTitle();
            $s["image"]=$imagineCacheManager->getBrowserPath( $section->getMedia()->getLink(), 'section_thumb_api');
            $ca=array();
            foreach ($section->getCategories() as $key => $category) {
                $c["id"]=$category->getId();
                $c["title"]=$category->getTitle();
                $c["image"]=$imagineCacheManager->getBrowserPath( $category->getMedia()->getLink(), 'section_thumb_api');
                $ca[]=$c;
            }
            $s["categories"]=$ca;
            $list[]=$s;
        }
        header('Content-Type: application/json'); 
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent=$serializer->serialize($list, 'json');
        return new Response($jsonContent);
    }
    public function addAction(Request $request)
    {
        $section= new Section();
        $form = $this->createForm(new SectionType(),$section);
        $em=$this->getDoctrine()->getManager();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if( $section->getFile()!=null ){
                $media= new Media();
                $media->setFile($section->getFile());
                $media->upload($this->container->getParameter('files_directory'));
                $em->persist($media);
                $em->flush();
                $section->setMedia($media);
                $max=0;
                $sections=$em->getRepository('AppBundle:Section')->findAll();
                foreach ($sections as $key => $value) {
                    if ($value->getPosition()>$max) {
                        $max=$value->getPosition();
                    }
                }
                $section->setPosition($max+1);
                $em->persist($section);
                $em->flush();
                $this->addFlash('success', 'Operation has been done successfully');
                return $this->redirect($this->generateUrl('app_section_index'));
            }else{
                $error = new FormError("Required image file");
                $form->get('file')->addError($error);
            }
        }
        return $this->render("AppBundle:Section:add.html.twig",array("form"=>$form->createView()));
    }
    public function indexAction()
    {
	    $em=$this->getDoctrine()->getManager();
        $sections=$em->getRepository('AppBundle:Section')->findBy(array(),array("position"=>"asc"));
	    return $this->render('AppBundle:Section:index.html.twig',array("sections"=>$sections));    
	}

    public function upAction(Request $request,$id)
    {
        $em=$this->getDoctrine()->getManager();
        $section=$em->getRepository("AppBundle:Section")->find($id);
        if ($section==null) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($section->getPosition()>1) {
            $p=$section->getPosition();
            $sections=$em->getRepository('AppBundle:Section')->findAll();
            foreach ($sections as $key => $value) {
                if ($value->getPosition()==$p-1) {
                    $value->setPosition($p);  
                }
            }
            $section->setPosition($section->getPosition()-1);
            $em->flush(); 
        }
        return $this->redirect($this->generateUrl('app_section_index'));
    }
    public function downAction(Request $request,$id)
    {
        $em=$this->getDoctrine()->getManager();
        $section=$em->getRepository("AppBundle:Section")->find($id);
        if ($section==null) {
            throw new NotFoundHttpException("Page not found");
        }
        $max=0;
        $sections=$em->getRepository('AppBundle:Section')->findBy(array(),array("position"=>"asc"));
        foreach ($sections  as $key => $value) {
            $max=$value->getPosition();  
        }
        if ($section->getPosition()<$max) {
            $p=$section->getPosition();
            foreach ($sections as $key => $value) {
                if ($value->getPosition()==$p+1) {
                    $value->setPosition($p);  
                }
            }
            $section->setPosition($section->getPosition()+1);
            $em->flush();  
        }
        return $this->redirect($this->generateUrl('app_section_index'));
    }
    public function deleteAction($id,Request $request){
        $em=$this->getDoctrine()->getManager();

        $section = $em->getRepository("AppBundle:Section")->find($id);
        if($section==null){
            throw new NotFoundHttpException("Page not found");
        }

        $form=$this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->add('Yes', 'submit')
            ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            
            //if (sizeof($section->getsections())==0) {
                $em->remove($section);
                $em->flush();

                $sections=$em->getRepository('AppBundle:Section')->findBy(array(),array("position"=>"asc"));

                $p=1;
                foreach ($sections as $key => $value) {
                    $value->setPosition($p); 
                    $p++; 
                }
                $em->flush();

                $this->addFlash('success', 'Operation has been done successfully');
            //}else{
             //   $this->addFlash('danger', 'Operation has been cancelled ,Your section not empty');   
            //}
            return $this->redirect($this->generateUrl('app_section_index'));
        }
        return $this->render('AppBundle:Section:delete.html.twig',array("form"=>$form->createView()));
    }
    public function editAction(Request $request,$id)
    {
        $em=$this->getDoctrine()->getManager();
        $section=$em->getRepository("AppBundle:Section")->find($id);
        if ($section==null) {
            throw new NotFoundHttpException("Page not found");
        }
        $form = $this->createForm(new SectionType(),$section);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
             if( $section->getFile()!=null ){
                $media= new Media();
                $media_old=$section->getMedia();
                $media->setFile($section->getFile());
                $media->upload($this->container->getParameter('files_directory'));
                $em->persist($media);
                $em->flush();
                $section->setMedia($media);
                $em->flush();
                $media_old->delete($this->container->getParameter('files_directory'));
                $em->remove($media_old);
                $em->flush();
            }
            $em->persist($section);
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirect($this->generateUrl('app_section_index'));
 
        }
        return $this->render("AppBundle:Section:edit.html.twig",array("form"=>$form->createView()));
    }
     public function viewAction(Request $request,$id)
    {
        $em=$this->getDoctrine()->getManager();
        $section=$em->getRepository("AppBundle:Section")->find($id);
        if ($section==null) {
            throw new NotFoundHttpException("Page not found");
        }
    
        return $this->render("AppBundle:Section:view.html.twig",array("section"=>$section));
    }
}
?>