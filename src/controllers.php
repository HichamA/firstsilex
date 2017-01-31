<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;


//use Symfony\Component\Form\Extension\Core\Type\EmailType;
//use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig', array());
})
->bind('homepage')
;

$app->get('/test/', function () use ($app) {
    return $app['twig']->render('test.html.twig', array());
})
    ->bind('testpage')
;

$app->get('/testparam/{id}', function ($id) use ($app) {
    return $app['twig']->render('testparam.html.twig',
        array(
            'param1' => $id
        ));
})
    ->bind('testparam')
;


$app->get('/listebd/{id}', function ($id) use ($app) {
    require_once 'tempdata/liste_bd_temp.php';
    return $app['twig']->render('listebd.html.twig',
        array(
            'param1' => $id,
            'listebd' => getListeBD()
        ));
})
    ->bind('listebd')
;

$app->match('/albumupdate-draft/{id}', function (Request $request, Silex\Application $app) {

    $form = $app['form.factory']->createBuilder(FormType::class)
//    ->add('crud', HiddenType::class, array(
//        'constraints' => array(new Assert\NotBlank())
//        ))
//    ->add('id', HiddenType::class, array(
//        'constraints' => array(new Assert\NotBlank())
//        ))
        ->add('album', TextType::class, array(
            'constraints' => array(new Assert\NotBlank(),
                new Assert\Length(array('min' => 2)),
                new Assert\Length(array('max' => 30))
            ),
            'attr' => array('class' => 'form-control')
        ))
        ->add('auteur', TextType::class, array(
            'constraints' => array(new Assert\NotBlank(),
                new Assert\Length(array('min' => 2)),
                new Assert\Length(array('max' => 30))
            ),
            'attr' => array('class' => 'form-control')
        ))
        ->add('editeur', TextType::class, array(
            'constraints' => array(new Assert\NotBlank(),
                new Assert\Length(array('min' => 2)),
                new Assert\Length(array('max' => 30))
            ),
            'attr' => array('class' => 'form-control')
        ))
        ->add('parution', DateType::class, array(
            'constraints' => array(new Assert\NotBlank()),
            'attr' => array('class' => 'form-control'),
            'widget' => 'single_text',
            // do not render as type="date", to avoid HTML5 date pickers
            'html5' => true,
            // add a class that can be selected in JavaScript
            //        'attr' => ['class' => 'js-datepicker'],
        ))
        ->add('save', SubmitType::class, array(
            'attr' => array('label' => 'Enregistrer', 'class' => 'btn btn-success'),
        ))
        ->add('reset', ResetType::class, array(
            'attr' => array('label' => 'Effacer', 'class' => 'btn btn-default'),
        ))
        ->getForm();

    if ($request->getMethod() == 'GET') {
        $id = (int) $request->get('id');
        $data = [];
        require_once 'tempdata/liste_bd_temp.php';
        $albums = getListeBD();

        if (!array_key_exists($id, $albums)) {
            // redirection vers une route spécifique si l'album n'existe pas
            return $app->redirect($app['url_generator']->generate('albumotfound'));
        } else {
            // Copie des données de l'album dans le tableau qui servira
            // à "peupler" le formulaire
            foreach ($albums[$id] as $key => $value) {
                $data[$key] = $app->escape($value);
            };
        }
    }

    if ($request->getMethod() == 'POST') {
        // les données envoyées par le formulaire sont réinjectées
        // dans le nouveau objet $form
        $form->handleRequest($request);
        // les données du formulaire sont récupérées dans un tableau
        // pour traitement ultérieur
        $data = $form->getData();
        // si le formulaire a été soumis et qu'aucune anomalie n'a été détectée
        if ($form->isSubmitted() && $form->isValid()) {
            if (isset($data['return'])) {
                return $app->redirect($app['url_generator']->generate('albumbdregister'));
            } else {
                return $app->redirect($app['url_generator']->generate('albumbdregister'));
            }
        } else {
            // si formulaire non soumis ou si anomalie, c'est reparti pour un tour
            error_log('formulaire BAD :(');
            error_log(var_export($data, true));
        }
    }
    // Affichage ou réaffichage du formulaire
    return $app['twig']->render(
        'albumbd-form-draft.html.twig', array(
            'form' => $form->createView(),
            'data' => $data
        ));
})
    ->bind('albumupdate-draft');






$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
