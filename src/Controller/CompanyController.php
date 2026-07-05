<?php

namespace App\Controller;

use App\Services\Interfaces\IReviewService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route(
        '/companies/{companyName}/show', 
        name: 'app_companies_show', 
        methods: ['GET']
    )]
    public function show(string $companyName): Response
    {
        $companyName = filter_var($companyName, FILTER_SANITIZE_STRING);

        if (empty($companyName)) {
            return $this->redirectToRoute('app_companies');
        }

        $reviews = $this->reviewService->getReviewsByCompanyName($companyName);

        return $this->render('company/show.html.twig', [
            'companyName' => $companyName,
            'reviews' => $reviews
        ]);
    }

    #[Route(
        '/companies/search/', 
        name: 'app_companies_search', 
        methods: ['GET'],
    )]
    public function search(Request $request): Response
    {
        $q = $request->query->get('q');

        //Sanitize
        $q = filter_var($q, FILTER_SANITIZE_STRING);

        if (empty($q)) {
            return $this->redirectToRoute('app_companies');
        }

        $companies = $this->reviewService->searchCompanies($q);

        return $this->render('company/index.html.twig', [
            'q' => $q,
            'companies' => $companies
        ]);
    }
}
