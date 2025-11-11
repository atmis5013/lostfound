<?php

namespace App\Controller;

use App\Entity\Item;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ItemController extends AbstractController
{
    #[Route('/items', name: 'app_items')]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        $items = $em->getRepository(Item::class)->findAll();
        $data = [];

        foreach ($items as $item) {
            $data[] = [
                'id' => $item->getId(),
                'title' => $item->getTitle(),
                'description' => $item->getDescription(),
                'status' => $item->getStatus(),
            ];
        }
        
        return $this->json($data);
    }
}
