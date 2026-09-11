<?php
// models/landingpage/Article.php

require_once __DIR__ . '/../../service/database/db.php';

class Article
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Ambil daftar artikel yang dipublikasikan dengan pagination & filter
     */
    public function getPublishedArticles($limit = 10, $offset = 0, $category = null, $search = null): array
    {
        $sql = "
            SELECT 
                id,
                title,
                slug,
                excerpt,
                thumbnail,
                category,
                author,
                views,
                created_at,
                updated_at
            FROM articles
            WHERE status = 'published'
        ";
        $params = [];

        if (!empty($category)) {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }

        if (!empty($search)) {
            $sql .= " AND (title LIKE :search OR excerpt LIKE :search2 OR content LIKE :search3)";
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Hitung total artikel terpublikasi untuk pagination
     */
    public function countPublishedArticles($category = null, $search = null): int
    {
        $sql = "SELECT COUNT(*) as total FROM articles WHERE status = 'published'";
        $params = [];

        if (!empty($category)) {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }

        if (!empty($search)) {
            $sql .= " AND (title LIKE :search OR excerpt LIKE :search2 OR content LIKE :search3)";
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Ambil detail artikel berdasarkan slug
     */
    public function findBySlug($slug)
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM articles
            WHERE slug = :slug AND status = 'published'
            LIMIT 1
        ");
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch();
    }

    /**
     * Ambil artikel berdasarkan ID
     */
    public function findById($id)
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM articles
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Ambil artikel terbaru untuk widget / homepage
     */
    public function getRecentArticles($limit = 3): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                id,
                title,
                slug,
                excerpt,
                thumbnail,
                category,
                author,
                views,
                created_at
            FROM articles
            WHERE status = 'published'
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Ambil semua kategori artikel unik
     */
    public function getCategories(): array
    {
        $stmt = $this->db->prepare("
            SELECT category, COUNT(*) as count
            FROM articles
            WHERE status = 'published'
            GROUP BY category
            ORDER BY count DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Tambah artikel baru
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO articles (
                title,
                slug,
                content,
                excerpt,
                thumbnail,
                category,
                author,
                status
            ) VALUES (
                :title,
                :slug,
                :content,
                :excerpt,
                :thumbnail,
                :category,
                :author,
                :status
            )
        ");

        $stmt->execute([
            ':title'     => $data['title'],
            ':slug'      => $data['slug'],
            ':content'   => $data['content'],
            ':excerpt'   => $data['excerpt'] ?? substr(strip_tags($data['content']), 0, 160) . '...',
            ':thumbnail' => $data['thumbnail'] ?? null,
            ':category'  => $data['category'] ?? 'Umum',
            ':author'    => $data['author'] ?? 'Admin',
            ':status'    => $data['status'] ?? 'published',
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update artikel
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        $allowed = ['title', 'slug', 'content', 'excerpt', 'thumbnail', 'category', 'author', 'status'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`$field` = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE articles SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Hapus artikel
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM articles WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Tambah view counter artikel
     */
    public function incrementViews(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE articles SET views = views + 1 WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }
}