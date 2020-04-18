<?php

namespace App\Builders;

use App\Caches\CategoryList as CategoryListCache;
use App\Repos\CourseCategory as CourseCategoryRepo;
use App\Repos\User as UserRepo;

class CourseList extends Builder
{

    public function handleCourses($courses)
    {
        $baseUrl = kg_ci_base_url();

        $list = [];

        foreach ($courses as $course) {

            $course['categories'] = [];
            $course['cover'] = $baseUrl . $course['cover'];
            $course['attrs'] = json_decode($course['attrs'], true);

            $result[] = [
                'id' => $course['id'],
                'title' => $course['title'],
                'cover' => $course['cover'],
                'summary' => $course['summary'],
                'categories' => $course['categories'],
                'market_price' => (float)$course['market_price'],
                'vip_price' => (float)$course['vip_price'],
                'study_expiry' => $course->study_expiry,
                'refund_expiry' => $course->refund_expiry,
                'rating' => (float)$course['rating'],
                'score' => (float)$course['score'],
                'model' => $course['model'],
                'level' => $course['level'],
                'attrs' => $course['attrs'],
                'user_count' => $course['user_count'],
                'lesson_count' => $course['lesson_count'],
                'comment_count' => $course['comment_count'],
                'review_count' => $course['review_count'],
                'favorite_count' => $course['favorite_count'],
            ];
        }

        return $list;
    }

    public function handleCategories($courses)
    {
        $categories = $this->getCategories($courses);

        foreach ($courses as $key => $course) {
            $courses[$key]['categories'] = $categories[$course['id']] ?? [];
        }

        return $courses;
    }

    public function handleUsers($courses)
    {
        $users = $this->getUsers($courses);

        foreach ($courses as $key => $course) {
            $courses[$key]['user'] = $users[$course['user_id']] ?? [];
        }

        return $courses;
    }

    protected function getCategories($courses)
    {
        $categoryListCache = new CategoryListCache();

        $categoryList = $categoryListCache->get();

        if (!$categoryList) {
            return [];
        }

        $mapping = [];

        foreach ($categoryList as $category) {
            $mapping[$category['id']] = [
                'id' => $category['id'],
                'name' => $category['name'],
            ];
        }

        $courseIds = kg_array_column($courses, 'id');

        $courseCategoryRepo = new CourseCategoryRepo();

        $relations = $courseCategoryRepo->findByCourseIds($courseIds);

        $result = [];

        foreach ($relations as $relation) {
            $categoryId = $relation->category_id;
            $courseId = $relation->course_id;
            $result[$courseId][] = $mapping[$categoryId] ?? [];
        }

        return $result;
    }

    protected function getUsers($courses)
    {
        $ids = kg_array_column($courses, 'user_id');

        $userRepo = new UserRepo();

        $users = $userRepo->findByIds($ids, ['id', 'name', 'avatar']);

        $baseUrl = kg_ci_base_url();

        $result = [];

        foreach ($users->toArray() as $user) {
            $user['avatar'] = $baseUrl . $user['avatar'];
            $result[$user['id']] = $user;
        }

        return $result;
    }

}
