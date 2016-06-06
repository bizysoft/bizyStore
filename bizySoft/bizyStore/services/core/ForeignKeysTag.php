<?php
namespace bizySoft\bizyStore\services\core;

use bizySoft\bizyStore\model\statements\JoinSpec;
use bizySoft\common\OptionsTag;
/**
 * Transform a 'foreignKey' tag to a useable form.
 *
 * Foreign keys can be specified for each database in the bizySoftConfig file as:
 * 
 * &lt;foreignKeys&gt;
 * foreignKeyTable(foreignKeyColumn1.foreignKeyColumn2 ...) => referencedTable(referencedColumn1.referencedColumn2, ...);
 * ...;
 * ...
 * &lt;/foreignKeys&gt;
 * 
 * foreignKeyTable can be repeated to allow multiple foreign keys per table.
 * The order of foreignKeyColumns/referencedColumns is significant, both sets must be in the required sequence.
 * A particular foreignKeyColumn can only occur in one foreign key declaration for a foreignKeyTable.
 * 
 * So we end up with the following in the order they are declared in the config file.
 * 
 * array(foreignKeyTable1 => array(array(foreignKeyColumn1 => referencedTable.referencedColumn1,
 *                                       foreignKeyColumn2 => referencedTable.referencedColumn2, 
 *                                       ...
 *                                      ),
 *                                 etc...
 *                                ),
 * 
 *       foreignKeyTable2 => array(array(foreignKeyColumn => referencedTable.referencedColumn, ..., ...),
 *                                 etc...
 *                                )
 *      );
 * 
 * This can then be transformed into our standard form by the ModelGenerator which will produce Schema files
 * with ForeignKeySchema and ForeignKeyRefereeSchema entries.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class ForeignKeysTag extends OptionsTag
{
	/**
	 * Construct with the name of the tag
	 * @param string $name
	 */
	public function __construct($name, $value)
	{
		parent::__construct($name);
		
		$foreignKeys = array();
		$options = $this->transform($value);
		foreach ($options as $fk)
		{
			$foreignReferenced = explode(self::KEY_VALUE_SEPARATOR , $fk);
			if (count($foreignReferenced) > 1)
			{
				$foreignKey = new JoinSpec(trim($foreignReferenced[0]));
				$referencedKey = new JoinSpec(trim($foreignReferenced[1]));
				$foreignTableName = $foreignKey->table;
				$foreignColumns = $foreignKey->columns;
				$referencedTableName = $referencedKey->table;
				$referencedColumns = $referencedKey->columns;
		
				if (!isset($foreignKeys[$foreignTableName]))
				{
					$foreignKeys[$foreignTableName] = array();
				}
		
				$foreignKeyBindings = array();
				for ($i=0; $i < count($foreignColumns); $i++)
				{
					$foreignKeyBindings[$foreignColumns[$i]] = $referencedTableName . "." . $referencedColumns[$i];
				}
				$foreignKeys[$foreignTableName][] = $foreignKeyBindings;
			}
		}
		$this->tags = $foreignKeys;
	}
}
?>