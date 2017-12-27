<?php

namespace IFRO\VerificadorDiarioBundle\Entity;

class Disciplina {

    private $nome;
    private $professor;
    private $cargaHoraria;
    private $aulasPresenciais;
    private $aulasNaoPresenciais;
    private $curso;
    private $serie;
    private $turma;
    private $primeiroConteudoBranco;
    private $conteudosInseridos;
    private $conteudosRegistrados;
    private $conteudosADefinir = array();

    public function __construct() {
        $this->primeiroConteudoBranco = new \DateTime();
    }

    function getCargaHoraria() {
        return $this->cargaHoraria;
    }

    function getAulasNaoPresenciais() {
        return $this->aulasNaoPresenciais;
    }

    function getAulasPresenciais() {
        return $this->aulasPresenciais;
    }

    function getNome() {
        return $this->nome;
    }

    function getSerie() {
        return $this->serie;
    }

    function getTurma() {
        return $this->turma;
    }

    function getCurso() {
        return $this->curso;
    }

    function getProfessor() {
        return $this->professor;
    }

    function setCargaHoraria($cargaHoraria) {
        $this->cargaHoraria = intval($cargaHoraria);
        return $this;
    }

    function setAulasNaoPresenciais($aulasNaoPresenciais) {
        $this->aulasNaoPresenciais = intval($aulasNaoPresenciais);
        return $this;
    }

    function setAulasPresenciais($aulasPresenciais) {
        $this->aulasPresenciais = intval($aulasPresenciais);
        return $this;
    }

    function setNome($nome) {
        $this->nome = $nome;
        return $this;
    }

    function setSerie($serie) {
        $this->serie = $serie;
        return $this;
    }

    function setTurma($turma) {
        $this->turma = $turma;
        return $this;
    }

    function setCurso($curso) {
        $this->curso = $curso;
        return $this;
    }

    function setProfessor($professor) {
        $this->professor = $professor;
        return $this;
    }

    function getPrimeiroConteudoBranco() {
        return $this->primeiroConteudoBranco->format('d/m/Y');
    }

    function setPrimeiroConteudoBranco($conteudosEmBranco) {
        if (empty($this->primeiroConteudoBranco)) {
            $this->primeiroConteudoBranco = \DateTime::createFromFormat('d/m/Y', $conteudosEmBranco);
        }
        return $this;
    }

    function getConteudosInseridos() {
        return $this->conteudosInseridos;
    }

    function setConteudosInseridos($conteudosInseridos) {
        $this->conteudosInseridos = intval($conteudosInseridos);
        return $this;
    }

    function getConteudosRegistrados() {
        if (is_null($this->conteudosRegistrados)) {
            return $this->getConteudosInseridos();
        }
        return $this->conteudosRegistrados;
    }

    function getConteudosADefinir() {
        return $this->conteudosADefinir;
    }

    function setConteudosRegistrados($conteudosRegistrados) {
        $this->conteudosRegistrados = $conteudosRegistrados;
        return $this;
    }

    function setConteudosADefinir($conteudosADefinir) {
        $this->conteudosADefinir = $conteudosADefinir;
        return $this;
    }

    function getAulasComFrequencia() {
        return $this->aulasNaoPresenciais + $this->aulasPresenciais;
    }

    function getMaximoNaoPresencial() {
        return floor($this->cargaHoraria * 0.2);
    }

    function vaiFecharCargaHoraria() {
        return $this->conteudosInseridos + $this->getMaximoNaoPresencial() >= $this->cargaHoraria;
    }

    function adicionarADefinir($conteudos) {
        foreach ($conteudos as $conteudo) {
            if (!in_array($conteudo, $this->conteudosADefinir)) {
                array_push($this->conteudosADefinir, $conteudo);
            }
        }
    }

    function getRegistroConteudoAtrasado() {
        $hoje = new \DateTime();
        $atraso = $hoje->diff($this->primeiroConteudoBranco);
        return $atraso->days >= 15;
    }

    function getRegistroFrequenciaAtrasado() {
        return $this->aulasPresenciais < $this->conteudosRegistrados;
    }

    function getCargaHorariaCompleta() {
        return $this->aulasPresenciais + $this->aulasNaoPresenciais >= $this->cargaHoraria;
    }

    function getAulasFaltantes() {
        return $this->cargaHoraria - ( $this->conteudosInseridos + $this->getMaximoNaoPresencial() );
    }

}
