<?php

namespace Bellwether\BWCMSBundle\Classes\Base;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\SecurityContext;
use Bellwether\BWCMSBundle\Entity\UserEntity;
use Bellwether\BWCMSBundle\Classes\Service\AdminService;
use Bellwether\BWCMSBundle\Classes\Service\ACLService;
use Bellwether\BWCMSBundle\Classes\Service\SiteService;
use Bellwether\BWCMSBundle\Classes\Service\ContentService;
use Bellwether\BWCMSBundle\Classes\Service\ContentQueryService;
use Bellwether\BWCMSBundle\Classes\Service\MediaService;
use Bellwether\BWCMSBundle\Classes\Service\MailService;
use Bellwether\BWCMSBundle\Classes\Service\PreferenceService;
use Bellwether\BWCMSBundle\Classes\Service\TemplateService;
use Symfony\Component\HttpFoundation\Session\Session;
use Bellwether\BWCMSBundle\Entity\ContentEntity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


abstract class BaseSkin extends ContainerAware
{

    private $thumbStyles = null;

    private $path = null;


    abstract public function getHomePageTemplate();

    abstract public function getLoginTemplate();

    abstract public function getForgotTemplate();

    abstract public function get404Template();

    abstract public function getPaginationTemplate();

    abstract public function getName();

    abstract public function initDefaultThumbStyles();

    abstract public function getNavigationRoutes();

    abstract public function getNavigationRoute($routeName);

    public function getThumbStyleDefault($thumbSlug)
    {
        if (is_null($this->thumbStyles)) {
            $this->initDefaultThumbStyles();
        }
        if (!isset($this->thumbStyles[$thumbSlug])) {
            return null;
        }
        return $this->thumbStyles[$thumbSlug];
    }

    public function addThumbStyleDefault($thumbSlug, $title, $mode, $width, $height)
    {
        if (is_null($this->thumbStyles)) {
            $this->thumbStyles = array();
        }

        $modes = array('resize', 'scaleResize', 'forceResize', 'cropResize', 'zoomCrop');
        if (!in_array($mode, $modes)) {
            throw new \InvalidArgumentException('Not supported mode. Available modes are ', join(', ', $modes));
        }

        $data = array();
        $data['name'] = $title;
        $data['mode'] = $mode;
        $data['width'] = $width;
        $data['height'] = $height;
        $this->thumbStyles[$thumbSlug] = $data;
    }

    /**
     * @return null|string
     */
    public function getPath()
    {
        if (null === $this->path) {
            $reflected = new \ReflectionObject($this);
            $this->path = dirname($reflected->getFileName());
        }
        return $this->path;
    }

    /**
     * @return string
     */
    public function getFolderName()
    {
        return basename($this->getPath());
    }

    /**
     * @param ContentEntity $contentEntity
     * @return string
     */
    public function getContentTemplate($contentEntity)
    {
        return $this->getTemplateName($this->cq()->getContentTemplate($contentEntity));
    }

    public function getTemplateName($templateFilename)
    {
        return "@" . $this->getFolderName() . DIRECTORY_SEPARATOR . $templateFilename;
    }

    /**
     * @var RequestStack
     *
     * @api
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function setRequestStack(RequestStack $requestStack = null)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return Request|null
     */
    public function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }

    public function getKernel()
    {
        return $this->container->get('kernel');
    }

    /**
     * @return EntityManager
     */
    public function em()
    {
        return $this->container->get('doctrine')->getManager();
    }

    /**
     * @return AdminService
     */
    public function admin()
    {
        return $this->container->get('BWCMS.Admin')->getManager();
    }

    /**
     * @return ACLService
     */
    public function acl()
    {
        return $this->container->get('BWCMS.ACL')->getManager();
    }

    /**
     * @return SiteService
     */
    public function sm()
    {
        return $this->container->get('BWCMS.Site')->getManager();
    }

    /**
     * @return ContentService
     */
    public function cm()
    {
        return $this->container->get('BWCMS.Content')->getManager();
    }

    /**
     * @return ContentQueryService
     */
    public function cq()
    {
        return $this->container->get('BWCMS.ContentQuery')->getManager();
    }

    /**
     * @return MediaService
     */
    public function mm()
    {
        return $this->container->get('BWCMS.Media')->getManager();
    }

    /**
     * @return PreferenceService
     */
    public function pref()
    {
        return $this->container->get('BWCMS.Preference')->getManager();
    }

    /**
     * @return TemplateService
     */
    public function tp()
    {
        return $this->container->get('BWCMS.Template')->getManager();
    }

    /**
     * @return Session
     */
    public function session()
    {
        return $this->container->get('session');
    }

    /**
     * @return MailService
     */
    public function mailer()
    {
        return $this->container->get('BWCMS.Mailer');
    }

    /**
     * @return SecurityContext
     */
    public function getSecurityContext()
    {
        return $this->container->get('security.context');
    }

    /**
     * @return \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }

    /**
     * @return \JMS\Serializer\Serializer
     */
    public function getSerializer()
    {
        return $this->container->get('serializer');
    }

    /**
     * @return UserEntity
     */
    public function getUser()
    {
        return $this->getSecurityContext()->getToken()->getUser();
    }


    public function dump($var, $maxDepth = 2, $stripTags = true)
    {
        print '<pre>';
        \Doctrine\Common\Util\Debug::dump($var, $maxDepth, $stripTags);
        print '</pre>';
    }


    /**
     * Checks if the attributes are granted against the current authentication token and optionally supplied object.
     *
     * @param mixed $attributes The attributes
     * @param mixed $object The object
     *
     * @throws \LogicException
     * @return bool
     */
    protected function isGranted($attributes, $object = null)
    {
        if (!$this->container->has('security.authorization_checker')) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        return $this->container->get('security.authorization_checker')->isGranted($attributes, $object);
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param string $route The name of the route
     * @param mixed $parameters An array of parameters
     * @param bool|string $referenceType The type of reference (one of the constants in UrlGeneratorInterface)
     *
     * @return string The generated URL
     *
     * @see UrlGeneratorInterface
     */
    public function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }
}
