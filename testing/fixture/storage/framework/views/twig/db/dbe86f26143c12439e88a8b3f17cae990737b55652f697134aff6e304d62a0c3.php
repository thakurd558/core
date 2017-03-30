<?php

/* C:\xampp\htdocs\src\core\testing\src/../fixture\resources/views/foo/bar.twig */
class __TwigTemplate_7c493d795ad454009f5b1005acaacb6537c8d5afb139d25b5f541a9b0c568fc1 extends TwigBridge\Twig\Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
    }

    public function getTemplateName()
    {
        return "C:\\xampp\\htdocs\\src\\core\\testing\\src/../fixture\\resources/views/foo/bar.twig";
    }

    public function getDebugInfo()
    {
        return array ();
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("{# empty Twig template #}
", "C:\\xampp\\htdocs\\src\\core\\testing\\src/../fixture\\resources/views/foo/bar.twig", "");
    }
}
