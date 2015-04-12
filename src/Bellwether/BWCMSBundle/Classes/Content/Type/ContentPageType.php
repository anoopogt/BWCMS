<?php

namespace Bellwether\BWCMSBundle\Classes\Content\Type;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Bellwether\BWCMSBundle\Classes\Constants\ContentFieldType;
use Bellwether\BWCMSBundle\Classes\Content\ContentType;
use Symfony\Component\Form\FormEvent;

use Bellwether\BWCMSBundle\Entity\ContentEntity;
use Bellwether\BWCMSBundle\Classes\Content\ContentTypeInterface;
use Bellwether\BWCMSBundle\Classes\Content\Form\SampleForm;


class ContentPageType Extends ContentType
{

    function __construct(ContainerInterface $container = null, RequestStack $request_stack = null)
    {
        $this->setContainer($container);
        $this->setRequestStack($request_stack);

        $this->setIsHierarchy(false);
        $this->setIsRootItem(false);

        $this->setIsSummaryEnabled(true);
        $this->setIsContentEnabled(true);
        $this->setIsSlugEnabled(true);
        $this->setIsUploadEnabled(false);
    }

    public function buildFields()
    {
        $this->addField('fieldContent', ContentFieldType::Content);
        $this->addField('gallery', ContentFieldType::Serialized);
    }

    public function buildForm()
    {
        $this->fb()->add('fieldContent', 'bwcms_content',
            array(
                'label' => 'Content'
            )
        );

        $this->fb()->add('gallery', 'bwcms_collection',
            array(
                'type' => new SampleForm(),
                'required' => false,
                'label' => 'Content',
                'allow_add' => true
            )
        );


    }

    public function getTemplates()
    {
        $templates = array();

        $templates['LeftImage.html.twig'] = 'Image Left';
        $templates['RightImage.html.twig'] = 'Image Right';

        return $templates;
    }

    public function validateForm(FormEvent $event)
    {

    }

    public function loadFormData(ContentEntity $content = null, Form $form = null)
    {
        return $form;
    }

    public function prepareEntity(ContentEntity $content = null, Form $form = null)
    {
        return $content;
    }

    public function getType()
    {
        return "Content.Page";
    }

    public function getSchema()
    {
        return "Default";
    }

    public function getName()
    {
        return "Page";
    }

}
