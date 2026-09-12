<?php
// models/admin/article/Article.php

require_once __DIR__ . '/../../../service/database/db.php';

class AdminArticle
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($search = null, $category = null, $status = null)
    {
        $sql = "SELECT id, title, slug, content, excerpt, thumbnail, category, author, status, views, created_at, updated_at 
                FROM articles WHERE 1=1";
        $params = [];

        if (!empty($category) && strtolower($category) !== 'semua') {
            $sql .= " AND (LOWER(category) = LOWER(:category) OR LOWER(category) LIKE LOWER(:category_like))";
            $params[':category'] = $category;
            $params[':category_like'] = '%' . $category . '%';
        }

        if (!empty($status) && strtolower($status) !== 'all' && strtolower($status) !== 'semua') {
            $sql .= " AND LOWER(status) = LOWER(:status)";
            $params[':status'] = $status;
        }

        if (!empty($search)) {
            $sql .= " AND (title LIKE :search OR excerpt LIKE :search OR author LIKE :search OR slug LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("
            SELECT id, title, slug, content, excerpt, thumbnail, category, author, status, views, created_at, updated_at
            FROM articles
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $title = trim($data['title'] ?? '');
        $slug = trim($data['slug'] ?? '');
        if (empty($slug)) {
            $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
            $slug = trim($slug, '-');
        }

        // Check if slug exists, if so append random
        $check = $this->db->prepare("SELECT id FROM articles WHERE slug = :slug LIMIT 1");
        $check->execute([':slug' => $slug]);
        if ($check->fetch()) {
            $slug .= '-' . substr(uniqid(), -4);
        }

        $content = $data['content'] ?? '';
        $excerpt = $data['excerpt'] ?? '';
        if (empty($excerpt)) {
            $excerpt = substr(strip_tags($content), 0, 160) . '...';
        }

        $thumbnail = $data['thumbnail'] ?? 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800';
        $category = !empty($data['category']) ? trim($data['category']) : 'Umum';
        $author = !empty($data['author']) ? trim($data['author']) : 'Admin';
        $status = !empty($data['status']) && in_array(strtolower($data['status']), ['published', 'draft']) ? strtolower($data['status']) : 'published';

        $stmt = $this->db->prepare("
            INSERT INTO articles (title, slug, content, excerpt, thumbnail, category, author, status, views, created_at, updated_at)
            VALUES (:title, :slug, :content, :excerpt, :thumbnail, :category, :author, :status, 0, NOW(), NOW())
        ");

        $stmt->execute([
            ':title' => $title,
            ':slug' => $slug,
            ':content' => $content,
            ':excerpt' => $excerpt,
            ':thumbnail' => $thumbnail,
            ':category' => $category,
            ':author' => $author,
            ':status' => $status
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $existing = $this->findById($id);
        if (!$existing) {
            return false;
        }

        $title = !empty($data['title']) ? trim($data['title']) : $existing['title'];
        $slug = !empty($data['slug']) ? trim($data['slug']) : $existing['slug'];
        
        // Ensure slug is clean
        $slug = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($slug));
        $slug = trim($slug, '-');

        // Check slug uniqueness
        $check = $this->db->prepare("SELECT id FROM articles WHERE slug = :slug AND id != :id LIMIT 1");
        $check->execute([':slug' => $slug, ':id' => (int)$id]);
        if ($check->fetch()) {
            $slug .= '-' . substr(uniqid(), -4);
        }

        $content = isset($data['content']) ? $data['content'] : $existing['content'];
        $excerpt = isset($data['excerpt']) ? $data['excerpt'] : $existing['excerpt'];
        $thumbnail = !empty($data['thumbnail']) ? $data['thumbnail'] : $existing['thumbnail'];
        $category = !empty($data['category']) ? trim($data['category']) : $existing['category'];
        $author = !empty($data['author']) ? trim($data['author']) : $existing['author'];
        $status = !empty($data['status']) && in_array(strtolower($data['status']), ['published', 'draft']) ? strtolower($data['status']) : $existing['status'];

        $stmt = $this->db->prepare("
            UPDATE articles 
            SET title = :title, slug = :slug, content = :content, excerpt = :excerpt, 
                thumbnail = :thumbnail, category = :category, author = :author, status = :status, updated_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            ':title' => $title,
            ':slug' => $slug,
            ':content' => $content,
            ':excerpt' => $excerpt,
            ':thumbnail' => $thumbnail,
            ':category' => $category,
            ':author' => $author,
            ':status' => $status,
            ':id' => (int)$id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM articles WHERE id = :id");
        return $stmt->execute([':id' => (int)$id]);
    }

    public function toggleStatus($id)
    {
        $existing = $this->findById($id);
        if (!$existing) return false;

        $newStatus = ($existing['status'] === 'published') ? 'draft' : 'published';
        $stmt = $this->db->prepare("UPDATE articles SET status = :status, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':status' => $newStatus, ':id' => (int)$id]);
        return $newStatus;
    }

    public function getStats()
    {
        $total = $this->db->query("SELECT COUNT(*) FROM articles")->fetchColumn();
        $published = $this->db->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();
        $draft = $this->db->query("SELECT COUNT(*) FROM articles WHERE status = 'draft'")->fetchColumn();
        $views = $this->db->query("SELECT COALESCE(SUM(views), 0) FROM articles")->fetchColumn();
        $categories = $this->db->query("SELECT DISTINCT category FROM articles")->fetchAll(PDO::FETCH_COLUMN);

        return [
            'total' => (int)$total,
            'published' => (int)$published,
            'draft' => (int)$draft,
            'views' => (int)$views,
            'categories' => $categories
        ];
    }
}
