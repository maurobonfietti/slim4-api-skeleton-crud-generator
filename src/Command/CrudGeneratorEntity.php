<?php

namespace App\Command;

class CrudGeneratorEntity
{
    public $list1, $list2, $list3, $list4, $list5, $list6;

    /**
     * Get Dynamic Parameters and Fields List.
     */
    public function getParamsAndFields($db, $entity)
    {
        $fields = $this->getEntityFields($db, $entity);
        foreach ($fields as $field) {
            $this->getFieldsList($field, $entity);
        }
        $this->cleanFields();
    }

    public function getEntityFields($db, $entity)
    {
        $query = "DESC `$entity`";
        $statement = $db->prepare($query);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function getFieldsList($field, $entity)
    {
        $this->list1.= sprintf("`%s`, ", $field['Field']);
        $this->list2.= sprintf(":%s, ", $field['Field']);
        $this->list3.= sprintf('$statement->bindParam(\'%s\', $%s->%s);%s', $field['Field'], $entity, $field['Field'], PHP_EOL);
        $this->list3.= sprintf("        %s", '');
        if ($field['Field'] != 'id') {
            $this->list4.= sprintf("`%s` = :%s, ", $field['Field'], $field['Field']);
            $this->list5.= sprintf("if (isset(\$data->%s)) {%s", $field['Field'], PHP_EOL);
            $this->list5.= sprintf("            $%s->%s = \$data->%s;%s", $entity, $field['Field'], $field['Field'], PHP_EOL);
            $this->list5.= sprintf("        }%s", PHP_EOL);
            $this->list5.= sprintf("        %s", '');
            if ($field['Null'] == "NO" && strpos($field['Type'], 'varchar') !== false) {
                $this->list6.= sprintf("'%s' => '%s',%s", $field['Field'], 'aaa', PHP_EOL);
                $this->list6.= sprintf("            %s", '');
            }
            if ($field['Null'] == "NO" && strpos($field['Type'], 'int') !== false) {
                $this->list6.= sprintf("'%s' => %s,%s", $field['Field'], 1, PHP_EOL);
                $this->list6.= sprintf("            %s", '');
            }
        }
    }

    public function cleanFields()
    {
        $this->list1 = substr_replace($this->list1, '', -2);
        $this->list2 = substr_replace($this->list2, '', -2);
        $this->list3 = substr_replace($this->list3, '', -8);
        $this->list4 = substr_replace($this->list4, '', -2);
        $this->list5 = substr_replace($this->list5, '', -9);
        $this->list6 = substr_replace($this->list6, '', -14);
    }
}
