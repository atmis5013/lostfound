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
        $items = $em->getRepository(Item::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->render('item/browse.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/items/{id}', name: 'item_show')]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $item = $em->getRepository(Item::class)->find($id);
        if (!$item) {
            throw $this->createNotFoundException('Item not found.');
        }

        return $this->render('item/show.html.twig', [
            'item' => $item,
        ]);
    }

    #[Route('/items/{id}/edit', name: 'item_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $item = $em->getRepository(Item::class)->find($id);
        if (!$item) {
            throw $this->createNotFoundException('Item not found.');
        }

        if ($item->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot edit this item.');
        }

        $form = $this->createForm(ItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Item updated successfully');
            return $this->redirectToRoute('item_show', ['id' => $item->getId()]);
        }

        return $this->render('item/edit.html.twig', [
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/items/{id}/delete', name: 'item_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $item = $em->getRepository(Item::class)->find($id);
        if (!$item) {
            throw $this->createNotFoundException('Item not found.');
        }

        if ($item->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot delete this item.');
        }

        if ($this->isCsrfTokenValid('delete_item_' . $item->getId(), $request->request->get('_token'))) {
            $em->remove($item);
            $em->flush();
            $this->addFlash('success', 'Item deleted successfully');
        }

        return $this->redirectToRoute('item_list');
    }
}
