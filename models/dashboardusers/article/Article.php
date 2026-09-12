<?php
// models/dashboardusers/article/Article.php

require_once __DIR__ . '/../../../service/database/db.php';

class Article
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($limit = null, $category = null, $search = null)
    {
        $sql = "SELECT id, title, slug, excerpt, thumbnail, category, author, views, created_at, updated_at 
                FROM articles 
                WHERE status = 'published'";
        $params = [];

        if (!empty($category) && strtolower($category) !== 'semua') {
            $sql .= " AND (LOWER(category) = LOWER(:category) OR LOWER(category) LIKE LOWER(:category_like))";
            $params[':category'] = $category;
            $params[':category_like'] = '%' . $category . '%';
        }

        if (!empty($search)) {
            $sql .= " AND (title LIKE :search OR excerpt LIKE :search OR content LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY id DESC";

        if ($limit !== null && (int)$limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findBySlug($slug)
    {
        $stmt = $this->db->prepare("
            SELECT id, title, slug, content, excerpt, thumbnail, category, author, views, created_at, updated_at
            FROM articles
            WHERE slug = :slug AND status = 'published'
            LIMIT 1
        ");
        $stmt->execute([':slug' => $slug]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($article) {
            $this->incrementViews($article['id']);
        }

        return $article;
    }

    public function incrementViews($id)
    {
        $stmt = $this->db->prepare("UPDATE articles SET views = views + 1 WHERE id = :id");
        $stmt->execute([':id' => (int)$id]);
    }

    public function getCategories()
    {
        $stmt = $this->db->query("
            SELECT category, COUNT(*) as count 
            FROM articles 
            WHERE status = 'published' 
            GROUP BY category 
            ORDER BY count DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
