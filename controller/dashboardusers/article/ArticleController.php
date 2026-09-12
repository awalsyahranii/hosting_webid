<?php
// controller/dashboardusers/article/ArticleController.php

require_once __DIR__ . '/../../../models/dashboardusers/article/Article.php';

class ArticleController
{
    private $articleModel;

    public function __construct()
    {
        $this->articleModel = new Article();
    }

    public function getAll($limit = null, $category = null, $search = null)
    {
        try {
            $articles = $this->articleModel->getAll($limit, $category, $search);
            $categories = $this->articleModel->getCategories();

            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Data artikel berhasil diambil',
                'data' => [
                    'articles' => $articles,
                    'total' => count($articles),
                    'categories' => $categories
                ]
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Gagal mengambil data artikel: ' . $e->getMessage()
            ];
        }
    }

    public function getBySlug($slug)
    {
        if (empty($slug)) {
            return [
                'status' => 'error',
                'code' => 400,
                'message' => 'Slug artikel tidak valid'
            ];
        }

        try {
            $article = $this->articleModel->findBySlug($slug);
            if (!$article) {
                return [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Artikel tidak ditemukan'
                ];
            }

            // Also get related articles in the same category
            $related = $this->articleModel->getAll(4, $article['category']);
            $filteredRelated = array_values(array_filter($related, function($item) use ($article) {
                return $item['slug'] !== $article['slug'];
            }));

            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Detail artikel berhasil diambil',
                'data' => [
                    'article' => $article,
                    'related' => array_slice($filteredRelated, 0, 3)
                ]
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Gagal mengambil detail artikel: ' . $e->getMessage()
            ];
        }
    }
}
