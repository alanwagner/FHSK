<?php
/**
 * Farther Horizon Site Kit
 *
 * @link       http://github.com/alanwagner/FHSK for the canonical source repository
 * @copyright Copyright (c) 2013 Farther Horizon SARL (http://www.fartherhorizon.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPLv3 License
 * @author    Alan Wagner (mail@alanwagner.org)
 */

namespace NdgTemplateTest\Model;

use NdgTemplate\Model\TemplateTable;
use NdgTemplate\Model\Template;
use Zend\Db\ResultSet\ResultSet;
use PHPUnit_Framework_TestCase;

/**
 * Tests on the TemplateTable class
 */
class TemplateTableTest extends PHPUnit_Framework_TestCase
{
    public function testFetchAllReturnsAllTemplates()
    {
        $resultSet = new ResultSet();
        $mockTableGateway = $this->getMock(
            'Zend\Db\TableGateway\TableGateway',
            array('select'),
            array(),
            '',
            false
        );
        $mockTableGateway->expects($this->once())
            ->method('select')
            ->with()
            ->will($this->returnValue($resultSet));

        $templateTable = new TemplateTable($mockTableGateway);

        $this->assertSame($resultSet, $templateTable->fetchAll());
    }

    public function testCanRetrieveATemplateByItsId()
    {
        $template = $this->getTemplateWithData();
        $resultSet = new ResultSet();
        $resultSet->setArrayObjectPrototype(new Template());
        $resultSet->initialize(array($template));

        $mockTableGateway = $this->getMock(
            'Zend\Db\TableGateway\TableGateway',
            array('select'),
            array(),
            '',
            false
        );
        $mockTableGateway->expects($this->once())
            ->method('select')
            ->with(array('id' => 420))
            ->will($this->returnValue($resultSet));

        $templateTable = new TemplateTable($mockTableGateway);

        $this->assertSame($template, $templateTable->getTemplate(420));
    }

    public function testCanRetrieveActiveTemplates()
    {
        $template = $this->getTemplateWithData();
        $resultSet = new ResultSet();
        $resultSet->setArrayObjectPrototype(new Template());
        $resultSet->initialize(array($template));

        $mockTableGateway = $this->getMock(
            'Zend\Db\TableGateway\TableGateway',
            array('select'),
            array(),
            '',
            false
        );
        $mockTableGateway->expects($this->once())
            ->method('select')
            ->with(array('is_archived' => 0))
            ->will($this->returnValue($resultSet));

        $templateTable = new TemplateTable($mockTableGateway);

        $this->assertSame($template, $templateTable->fetchByIsArchived(0)->current());
    }

    public function testCanRetrieveArchivedTemplates()
    {
        $template = $this->getTemplateWithData();
        $template->is_archived = 1;
        $resultSet = new ResultSet();
        $resultSet->setArrayObjectPrototype(new Template());
        $resultSet->initialize(array($template));

        $mockTableGateway = $this->getMock(
            'Zend\Db\TableGateway\TableGateway',
            array('select'),
            array(),
            '',
            false
        );
        $mockTableGateway->expects($this->once())
            ->method('select')
            ->with(array('is_archived' => 1))
            ->will($this->returnValue($resultSet));

        $templateTable = new TemplateTable($mockTableGateway);

        $this->assertSame($template, $templateTable->fetchByIsArchived(1)->current());
    }

    public function testCanDeleteATemplateByItsId()
    {
        $mockTableGateway = $this->getMock(
            'Zend\Db\TableGateway\TableGateway',
            array('delete'),
            array(),
            '',
            false
        );
        $mockTableGateway->expects($this->once())
            ->method('delete')
            ->with(array('id' => 420));

        $templateTable = new TemplateTable($mockTableGateway);
        $templateTable->deleteTemplate(420);
    }

    public function testSaveTemplateWillInsertNewTemplatesIfTheyDontAlreadyHaveAnId()
    {
        $templateData = $this->getDataArray();
        $created = $templateData['created_at'];
        unset($templateData['id']);
        unset($templateData['created_at']);

        $template     = new Template();
        $template->exchangeArray($templateData);
        $templateData['created_at'] = $created;

        $mockTableGateway = $this->getMock(
            'Zend\Db\TableGateway\TableGateway',
            array('insert'),
            array(),
            '',
            false
        );
        $mockTableGateway->expects($this->once())
            ->method('insert')
            ->with($templateData);

        $templateTable = new TemplateTable($mockTableGateway);
        $templateTable->saveTemplate($template);
    }

    public function testSaveTemplateWillUpdateExistingTemplatesIfTheyAlreadyHaveAnId()
    {
        $template = $this->getTemplateWithData();
        $resultSet = new ResultSet();
        $resultSet->setArrayObjectPrototype(new Template());
        $resultSet->initialize(array($template));

        $mockTableGateway = $this->getMock(
            'Zend\Db\TableGateway\TableGateway',
            array('select', 'update'),
            array(),
            '',
            false
        );
        $mockTableGateway->expects($this->once())
            ->method('select')
            ->with(array('id' => 420))
            ->will($this->returnValue($resultSet));

        $templateData = $this->getDataArray();
        unset($templateData['id']);
        unset($templateData['created_at']);

        $mockTableGateway->expects($this->once())
            ->method('update')
            ->with($templateData, array('id' => 420));

        $templateTable = new TemplateTable($mockTableGateway);
        $templateTable->saveTemplate($template);
    }

    public function testExceptionIsThrownWhenGettingNonExistentTemplate()
    {
        $resultSet = new ResultSet();
        $resultSet->setArrayObjectPrototype(new Template());
        $resultSet->initialize(array());

        $mockTableGateway = $this->getMock(
            'Zend\Db\TableGateway\TableGateway',
            array('select'),
            array(),
            '',
            false
        );
        $mockTableGateway->expects($this->once())
            ->method('select')
            ->with(array('id' => 420))
            ->will($this->returnValue($resultSet));

        $templateTable = new TemplateTable($mockTableGateway);

        try {
            $templateTable->getTemplate(420);
        }
        catch (\Exception $e) {
            $this->assertSame('Could not find row 420', $e->getMessage());
            return;
        }

        $this->fail('Expected exception was not thrown');
    }

    /**
     * Get Template entity initialized with standard data
     * @return NdgTemplate\Model\Template
     */
    protected function getTemplateWithData()
    {
        $template = new Template();
        $data  = $this->getDataArray();
        $template->exchangeArray($data);

        return $template;
    }

    /**
     * Get standard data as array
     * @return array
     */
    protected function getDataArray()
    {
        return array(
            'id'          => 420,
            'name'        => 'template name',
            'content'     => "1 2 3\n2 1 3\n3 1 2",
            'description' => 'N=3, Z=2',
            'is_archived' => 0,
            'created_at'  => date('Y-m-d H:i:s'),
        );
    }
}