<?php

namespace App\Controller;

use App\Entity\Item;
use App\Form\ItemType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ItemWebController extends AbstractController
{
    #[Route('/items/new', name: 'item_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $item = new Item();

        // Set the currently logged-in user as owner
        $item->setOwner($this->getUser());

        $form = $this->createForm(ItemType::class, $item);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $item->setCreatedAt(new \DateTimeImmutable());
            $em->persist($item);
            $em->flush();

            $this->addFlash('success', 'Item added successfully!');
           
            return $this->redirectToRoute('item_list');
        }

        return $this->render('item/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/items/list', name: 'item_list')]
    public function list(EntityManagerInterface $em): Response
    {
         $items = $em->getRepository(Item::class)->findBy([
            'owner' => $this->getUser()
        ]);

        return $this->render('item/list.html.twig', [
            'items' => $items,
        ]);
    }


    #[Route('/items/browse', name: 'item_browse')]
    public function browse(EntityManagerInterface $em): Response
    {
          // Show all items regardless of owner
        $items = $em->getRepository(Item::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->render('item/browse.html.twig', [
             'items' => $items,
    ]);
    }

}
