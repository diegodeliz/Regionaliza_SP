<?php

class Crud{

    private $db;

    /**
     *
     * Seta as vari�veis de conex�o
     *
     */
    public function __set($name, $value){
        switch($name){
            case 'Username':
            $this->Username = $value;
            break;

            case 'Password':
            $this->Password = $value;
            break;

            case 'Dsn':
            $this->Dsn = $value;
            break;

            default:
            throw new Exception("$name � inv�lido");
        }
    }

    /**
     *
     * Verifica quais vari�veis possuem valores padr�es.
     *
     */
    public function __isset($name){
        switch($name){
            case 'Username':
            $this->Username = null;
            break;

            case 'Password':
            $this->Password = null;
            break;
        }
    }

	/**
	 *
	 * Conecta com o banco de dados e seta o modo de erro para exe��es
	 *
	 * @Throws PDOException quando haja uma falha
	 *
	 */
	public function conn(){
		isset($this->Username);
		isset($this->Password);
		if (!$this->db instanceof PDO){
			$this->db = new PDO($this->Dsn, $this->Username, $this->Password);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
	}


	/***
	 *
	 * Seleciona valores de uma tabela
	 *
	 * Acesso p�blico
	 *
	 * Param�tro $table � o nome da tabela a ser buscado os dados
	 *
	 * Param�tro string $fieldname � o nome do campo da tabela a ser buscado os valores
	 *
	 * Param�tro string $id � a chave prim�ria da tabela
	 *
	 * Retorna um array em caso de sucesso ou throw PDOException em caso de falha
	 *
	 */
	public function dbSelect($table, $fieldname=null, $id=null){
		$this->conn();
		$sql = "SELECT * FROM `$table` WHERE `$fieldname`=:id";
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':id', $id);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}


	/**
	 *
	 * Executa uma query select crua, formulada pelo usu�rio (Utiliza-se para jun��es e querys e selects mais complexos)
	 *
	 * Acesso p�blico
	 *
	 * Param�tro string $sql � a query em si
	 *
	 * Retorna um array
	 *
	 */
	public function rawSelect($sql){
		$this->conn();
		return $this->db->query($sql);
	}


	/**
	 *
	 * Insere um valor em uma tabela
	 *
	 * Acesso p�blico
	 *
	 * Param�tro string $table � o nome da tabela a ser buscado os dados
	 *
	 * Param�tro array $values  s�o os valores a serem inseridos
	 *
	 * Retona o �ltimo ID inserido com sucesso ou throw PDOexeption em caso de falha
	 *
	 */
	public function dbInsert($table, $values){
		$this->conn();
		$fieldnames = array_keys($values[0]);
		$size = sizeof($fieldnames);
		$i = 1;
		$sql = "INSERT INTO $table";
		$fields = '( ' . implode(' ,', $fieldnames) . ' )';
		$bound = '(:' . implode(', :', $fieldnames) . ' )';
		$sql .= $fields.' VALUES '.$bound;
		
		$stmt = $this->db->prepare($sql);
		$valores = "(";
		$controle_data = 0;
		$voltas = count($fields)/2;

		$i = 1;
		foreach($values as $vals){
			$contador = count($vals);
			
			if(!in_array("NOW()", $vals)){
				$stmt->execute($vals);
			}else{
				foreach($vals as $valor){
					if(is_int($valor) or is_float($valor)){
						$valores .= $valor.",";
					}else{
						if($valor === (string)'NOW()'){
							$valores .= str_replace("'", "", $valor);
							if($i <> $contador){
								$valores = $valores.",";
							}else{
								$valores = $valores;
							}
						}else{
							if($i <> $contador){
								$valores .= "'".$valor."'".",";
							}else{
								$valores .= "'".$valor."'";
							}
						}
					}
					$i++;
				}
				$valores .= ")";
				$controle_data = 1;	
			}
		}

		if($controle_data == 1){
			$final = "INSERT INTO $table $fields VALUES $valores";
			$this->db->query($final);
		}
	}

	/**
	 *
	 * Atualiza o valor de um registro em uma tabela
	 *
	 * Acesso p�blico
	 *
	 * Param�tro string $table � o nome da tabela a ser alterado os dados
	 *
	 * Param�tro string $fieldname � o nome do campo da tabela a ser alterado os valores
	 *
	 * Param�tro string $value � o novo valor
	 *
	 * Param�tro string $pk � a chave prim�ria
	 *
	 * Param�tro string $id � o ID
	 *
	 * Throws PDOException em caso de falha
	 *
	 */
	public function dbUpdate($table, $fieldname, $value, $pk, $id){
		$this->conn();
		if($value === (string)'NOW()'){
			$sql = "UPDATE `$table` SET `$fieldname`=NOW() WHERE `$pk` = :id";
		}else{
			$sql = "UPDATE `$table` SET `$fieldname`='{$value}' WHERE `$pk` = :id";
		}
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':id', $id, PDO::PARAM_STR);
		$total = $stmt->execute();
	}
	
	public function dbUpdateAll($table, $value, $pk, $id){
		$this->conn();
		$sql = "UPDATE `$table` SET ".$value." WHERE `$pk` = :id";
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':id', $id, PDO::PARAM_STR);
		$total = $stmt->execute();
	}


	/**
	 *
	 * Deleta um registro de uma tabela
	 *
	 * Acesso p�blico
	 *
	 * Param�tro string $table � o nome da tabela a ser deletado os dados
	 *
	 * Param�tro string $fieldname � o nome do campo da tabela a ser deletado os valores
	 *
	 * Param�tro string $id � o ID
	 *
	 * Throws PDOException em caso de falha
	 *
	 */
	public function dbDelete($table, $fieldname, $id){
		try{
			$this->conn();
			$sql = "DELETE FROM `$table` WHERE `$fieldname` = :id";
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(':id', $id, PDO::PARAM_STR);
			$stmt->execute();
		}catch(PDOException $i){
        	//se houver exce��o, exibe
        	print "Erro: <code>" . $i->getMessage() . "</code>";
		}
	}
	
	public function dbDeletePar($table, $parametros){
		try{
			$this->conn();
			$sql = "DELETE FROM `$table` WHERE $parametros";
			$stmt = $this->db->prepare($sql);
			$stmt->execute();
		}catch(PDOException $i){
        	//se houver exce��o, exibe
        	print "Erro: <code>" . $i->getMessage() . "</code>";
		}
	}
	
	public function dbDeleteAll($table){
		try{
			$this->conn();
			$sql = "TRUNCATE TABLE `$table`";
			$stmt = $this->db->prepare($sql);
			$stmt->execute();
		}catch(PDOException $i){
        	//se houver exce��o, exibe
        	print "Erro: <code>" . $i->getMessage() . "</code>";
		}
	}
} /*** Fim da classe ***/

?>