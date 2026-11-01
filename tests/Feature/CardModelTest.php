<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Model\Card;
use App\Model\Category;

/**
 * 卡片与分类 Model 测试：覆盖 CRUD、字段白名单、搜索与 LIKE 转义。
 */
final class CardModelTest extends ModelTestCase
{
    private function category(): Category
    {
        return new Category($this->pdo);
    }

    private function card(): Card
    {
        return new Card($this->pdo);
    }

    public function testCreateAndFindCard(): void
    {
        $catId = $this->category()->create('开发', 0, 1);
        $cardId = $this->card()->create([
            'category_id' => $catId,
            'title' => 'GitHub',
            'link' => 'https://github.com',
            'is_active' => 1,
        ]);

        self::assertGreaterThan(0, $cardId);

        $found = $this->card()->find($cardId);
        self::assertNotNull($found);
        self::assertSame('GitHub', $found['title']);
        self::assertSame('开发', $found['category_name']);
    }

    public function testFindReturnsNullForMissingCard(): void
    {
        self::assertNull($this->card()->find(9999));
    }

    public function testInactiveCardsAreHiddenByDefault(): void
    {
        $id = $this->card()->create(['title' => '隐藏', 'is_active' => 0]);
        self::assertNull($this->card()->find($id));
        self::assertNotNull($this->card()->find($id, false));
    }

    /** 字段白名单：不在白名单的列不能被写入。 */
    public function testUnknownFieldsAreIgnoredOnCreate(): void
    {
        $id = $this->card()->create([
            'title' => '正常标题',
            'click_count' => 9999,
            'created_at' => '2000-01-01 00:00:00',
        ]);
        $row = $this->card()->find($id);
        self::assertSame(0, (int) $row['click_count'], 'click_count 不应被 create 写入');
    }

    public function testUpdateOnlyTouchesAllowedFields(): void
    {
        $id = $this->card()->create(['title' => '原标题', 'is_active' => 1]);
        $this->card()->update($id, ['title' => '新标题', 'click_count' => 500]);

        $row = $this->card()->find($id);
        self::assertSame('新标题', $row['title']);
        self::assertSame(0, (int) $row['click_count'], 'click_count 不应被 update 写入');
    }

    public function testDeleteRemovesCard(): void
    {
        $id = $this->card()->create(['title' => '待删除']);
        $this->card()->delete($id);
        self::assertNull($this->card()->find($id, false));
    }

    public function testIncrementClickUpdatesCounter(): void
    {
        $id = $this->card()->create(['title' => '计数器']);
        $this->card()->incrementClick($id);
        $this->card()->incrementClick($id);
        self::assertSame(2, (int) $this->card()->find($id)['click_count']);
    }

    /** 搜索：命中标题、分类名，不命中无关内容。 */
    public function testSearchMatchesTitleAndCategoryName(): void
    {
        $devId = $this->category()->create('开发', 0, 1);
        $this->category()->create('影视', 1, 1);
        $this->card()->create(['category_id' => $devId, 'title' => 'GitHub 仓库', 'is_active' => 1]);
        $this->card()->create(['category_id' => $devId, 'title' => '无关标题', 'is_active' => 1]);

        $byTitle = $this->card()->search('github');
        self::assertCount(1, $byTitle);
        self::assertSame('GitHub 仓库', $byTitle[0]['title']);

        $byCategory = $this->card()->search('开发');
        self::assertCount(2, $byCategory);
    }

    /** 停用的卡片不应出现在搜索结果中。 */
    public function testSearchExcludesInactiveCards(): void
    {
        $this->card()->create(['title' => '隐藏卡片', 'is_active' => 0]);
        self::assertCount(0, $this->card()->search('隐藏'));
    }

    /** LIKE 通配符必须被转义为字面量，不能变成全表匹配。 */
    public function testSearchEscapesLikeWildcards(): void
    {
        $this->card()->create(['title' => '普通标题', 'is_active' => 1]);

        self::assertCount(0, $this->card()->search('%'), '% 应被当作字面量');
        self::assertCount(0, $this->card()->search('_'), '_ 应被当作字面量');
        self::assertCount(0, $this->card()->search('%普通%'));
    }

    /** 含 % 的真实内容仍可被搜到。 */
    public function testSearchFindsLiteralPercentInContent(): void
    {
        $this->card()->create(['title' => '折扣 50% OFF', 'is_active' => 1]);
        $found = $this->card()->search('50%');
        self::assertCount(1, $found);
        self::assertSame('折扣 50% OFF', $found[0]['title']);
    }
}
