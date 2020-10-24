<?php

class CvJobposTest extends CDbTestCase
{
	private $basePathOld = null;

	public $fixtures = array(
		'cv_jobpos_group' => 'CvJobposGroup',
		'cv_jobpos' => 'CvJobpos',
	);

	protected function setUp()
	{
		$fixturePath = Yii::getPathOfAlias('application.modules.cv.tests.fixtures');
		if (is_dir($fixturePath)) {
			$this->basePathOld = $this->getFixtureManager()->basePath;
			$this->getFixtureManager()->basePath = $fixturePath;
		}
		$this->getFixtureManager()->prepare();

		parent::setUp();
	}

	protected function tearDown()
	{
		$this->getFixtureManager()->truncateTable('cv_jobpos');
		$this->getFixtureManager()->truncateTable('cv_jobpos_group');

		parent::tearDown();
		if (null !== $this->basePathOld) {
			$this->getFixtureManager()->basePath = $this->basePathOld;
		}
	}

	public function testRead()
	{
		$jobposGroup1 = $this->cv_jobpos_group('softwareDevelopment');
		$this->assertTrue($jobposGroup1 instanceof CvJobposGroup);
		$this->assertEquals('Software Development', $jobposGroup1->title);

		$jobpos1 = $this->cv_jobpos('frotendDeveloper');
		$this->assertTrue($jobpos1 instanceof CvJobpos);
		$this->assertEquals('Frontend Developer', $jobpos1->title);
		$this->assertEquals($jobposGroup1->title, $jobpos1->cvJobposGroup->title);
	}

	public function testCreate()
	{
		$jobpos = new CvJobpos;
		$jobpos->setAttributes(
			array(
				'title' => 'Test Job Position',
				'cv_jobpos_group_id' => 1,
				'is_active' => '1',
				'date_added' => time(),
				'date_modified' => time(),
			)
		);
		$this->assertTrue($jobpos->save(false));
		// READ back the newly created Project to ensure the creation worked
		$retrievedRecord = CvJobpos::model()->findByPk($jobpos->id);
		$this->assertTrue($retrievedRecord instanceof CvJobpos);
		$this->assertEquals('Test Job Position', $retrievedRecord->title);
	}

	public function testUpdate()
	{
		$jobpos = $this->cv_jobpos('frotendDeveloper');
		$updatedTitle = 'Test Frontend Developer';
		$jobpos->title = $updatedTitle;
		$this->assertTrue($jobpos->save(false));

		//read back the record again to ensure the update worked
		$updatedRecord = CvJobpos::model()->findByPk($jobpos->id);
		$this->assertTrue($updatedRecord instanceof CvJobpos);
		$this->assertEquals($updatedTitle, $updatedRecord->title);
	}

	public function testDelete()
	{
		$jobpos = $this->cv_jobpos('frotendDeveloper');
		$savedId = $jobpos->id;
		$this->assertTrue($jobpos->delete());
		$deletedRecord = CvJobpos::model()->findByPk($savedId);
		$this->assertEquals(null, $deletedRecord);
	}
}
