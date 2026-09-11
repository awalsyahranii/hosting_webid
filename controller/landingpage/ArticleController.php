<?php
// controller/landingpage/ArticleController.php

require_once __DIR__ . '/../../models/landingpage/Article.php';

class ArticleController
{
    private Article $articleModel;

    public function __construct()
    {
        $this->articleModel = new Article();
    }

    public function getArticles(array $params = []): array
    {
        try {
            $limit = max(1, min(50, (int) ($params['limit'] ?? 9)));
            $page = max(1, (int) ($params['page'] ?? 1));
            $offset = ($page - 1) * $limit;
            $category = !empty($params['category']) ? trim((string) $params['category']) : null;
            $search = !empty($params['search']) ? trim((string) $params['search']) : null;

            $articles = $this->articleModel->getPublishedArticles($limit, $offset, $category, $search);
            $total = $this->articleModel->countPublishedArticles($category, $search);
            $totalPages = ceil($total / $limit);

            return [
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Daftar artikel berhasil diambil',
                'data'    => [
                    'articles'    => $articles,
                    'pagination'  => [
                        'total'        => $total,
                        'page'         => $page,
                        'limit'        => $limit,
                        'total_pages'  => $totalPages,
                        'has_more'     => $page < $totalPages
                    ]
                ]
            ];
        } catch (Throwable $e) {
            return [
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Gagal mengambil daftar artikel: ' . $e->getMessage()
            ];
        }
    }

    public function getDetail(string $slug): array
    {
        try {
            $slug = trim($slug);
            if ($slug === '') {
                return [
                    'status'  => 'error',
                    'code'    => 400,
                    'message' => 'Slug artikel tidak boleh kosong'
                ];
            }

            $article = $this->articleModel->findBySlug($slug);
            if (!$article) {
                return [
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Artikel tidak ditemukan'
                ];
            }

            // Increment views
            $this->articleModel->incrementViews((int) $article['id']);
            $article['views'] = ((int) $article['views']) + 1;

            // Related articles
            $related = $this->articleModel->getPublishedArticles(3, 0, $article['category']);
            // Filter out current article
            $related = array_values(array_filter($related, fn($a) => (int)$a['id'] !== (int)$article['id']));

            return [
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Detail artikel berhasil diambil',
                'data'    => [
                    'article' => $article,
                    'related' => $related
                ]
            ];
        } catch (Throwable $e) {
            return [
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Gagal mengambil detail artikel: ' . $e->getMessage()
            ];
        }
    }

    public function getRecent(int $limit = 3): array
    {
        try {
            $articles = $this->articleModel->getRecentArticles($limit);
            return [
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Artikel terbaru berhasil diambil',
                'data'    => $articles
            ];
        } catch (Throwable $e) {
            return [
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Gagal mengambil artikel terbaru: ' . $e->getMessage()
            ];
        }
    }

    public function getCategories(): array
    {
        try {
            $categories = $this->articleModel->getCategories();
            return [
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Kategori artikel berhasil diambil',
                'data'    => $categories
            ];
        } catch (Throwable $e) {
            return [
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Gagal mengambil kategori: ' . $e->getMessage()
            ];
        }
    }

    public function create(array $data): array
    {
        try {
            $title = trim((string) ($data['title'] ?? ''));
            $content = trim((string) ($data['content'] ?? ''));

            if ($title === '' || $content === '') {
                return [
                    'status'  => 'error',
                    'code'    => 400,
                    'message' => 'Judul dan konten artikel wajib diisi'
                ];
            }

            $slug = !empty($data['slug'])
                ? $this->slugify($data['slug'])
                : $this->slugify($title);

            // Cek duplikasi slug
            $existing = $this->articleModel->findBySlug($slug);
            if ($existing) {
                $slug .= '-' . time();
            }

            $data['slug'] = $slug;
            $id = $this->articleModel->create($data);

            return [
                'status'  => 'success',
                'code'    => 201,
                'message' => 'Artikel berhasil dibuat',
                'data'    => [
                    'id'   => $id,
                    'slug' => $slug
                ]
            ];
        } catch (Throwable $e) {
            return [
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Gagal membuat artikel: ' . $e->getMessage()
            ];
        }
    }

    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'artikel-' . uniqid() : $text;
    }
}