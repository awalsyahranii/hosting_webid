<?php
// controller/admin/article/ArticleController.php

require_once __DIR__ . '/../../../models/admin/article/Article.php';

class AdminArticleController
{
    private $articleModel;

    public function __construct()
    {
        $this->articleModel = new AdminArticle();
    }

    public function getAll($search = null, $category = null, $status = null)
    {
        try {
            $articles = $this->articleModel->getAll($search, $category, $status);
            $stats = $this->articleModel->getStats();

            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Data artikel admin berhasil dimuat',
                'data' => [
                    'articles' => $articles,
                    'total' => count($articles),
                    'stats' => $stats
                ]
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Gagal memuat artikel admin: ' . $e->getMessage()
            ];
        }
    }

    public function getById($id)
    {
        if (empty($id) || !is_numeric($id)) {
            return ['status' => 'error', 'code' => 400, 'message' => 'ID artikel tidak valid'];
        }

        try {
            $article = $this->articleModel->findById((int)$id);
            if (!$article) {
                return ['status' => 'error', 'code' => 404, 'message' => 'Artikel tidak ditemukan'];
            }

            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Detail artikel berhasil dimuat',
                'data' => $article
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Gagal memuat artikel: ' . $e->getMessage()];
        }
    }

    public function create($data)
    {
        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            return ['status' => 'error', 'code' => 400, 'message' => 'Judul artikel wajib diisi'];
        }

        try {
            $newId = $this->articleModel->create($data);
            $created = $this->articleModel->findById($newId);

            return [
                'status' => 'success',
                'code' => 201,
                'message' => 'Artikel berhasil dibuat',
                'data' => $created
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Gagal membuat artikel: ' . $e->getMessage()];
        }
    }

    public function update($id, $data)
    {
        if (empty($id) || !is_numeric($id)) {
            return ['status' => 'error', 'code' => 400, 'message' => 'ID artikel tidak valid'];
        }

        try {
            $success = $this->articleModel->update((int)$id, $data);
            if (!$success) {
                return ['status' => 'error', 'code' => 404, 'message' => 'Artikel tidak ditemukan untuk diupdate'];
            }

            $updated = $this->articleModel->findById((int)$id);

            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Artikel berhasil diperbarui',
                'data' => $updated
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Gagal memperbarui artikel: ' . $e->getMessage()];
        }
    }

    public function delete($id)
    {
        if (empty($id) || !is_numeric($id)) {
            return ['status' => 'error', 'code' => 400, 'message' => 'ID artikel tidak valid'];
        }

        try {
            $this->articleModel->delete((int)$id);
            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Artikel berhasil dihapus'
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Gagal menghapus artikel: ' . $e->getMessage()];
        }
    }

    public function toggleStatus($id)
    {
        if (empty($id) || !is_numeric($id)) {
            return ['status' => 'error', 'code' => 400, 'message' => 'ID artikel tidak valid'];
        }

        try {
            $newStatus = $this->articleModel->toggleStatus((int)$id);
            if (!$newStatus) {
                return ['status' => 'error', 'code' => 404, 'message' => 'Artikel tidak ditemukan'];
            }

            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Status artikel diubah menjadi ' . $newStatus,
                'data' => ['id' => (int)$id, 'status' => $newStatus]
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Gagal mengubah status: ' . $e->getMessage()];
        }
    }
}
