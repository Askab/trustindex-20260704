<?php

namespace App\Controller;

use App\Services\Interfaces\IReviewService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CompanyController extends AbstractController
{
    public function __construct(
        protected IReviewService $reviewService
    ) {}

    #[Route('/companies', name: 'app_companies', methods: ['GET'])]
    public function index(): Response
    {
        $companies = $this->reviewService->getStatistics();

        return $this->render('company/index.html.twig', [
            'companies' => $companies
        ]);
    }
}
