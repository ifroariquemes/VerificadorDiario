<?php

namespace IFRO\VerificadorDiarioBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use IFRO\VerificadorDiarioBundle\Entity\Disciplina;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class VerificadorController extends Controller {

    private $pdf;
    private $userMsg = "";
    private $disciplinas = array();

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request) {
        $form = $this->createFormBuilder(null, ['csrf_protection' => false])
                ->add('arquivo', FileType::class, ['label' => 'Arquivo', 'required' => true])
                ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->validarArquivo($form->get('arquivo')->getData()) === true) {
                $this->carregarDisciplinas();
            }
        }

        return $this->render('verificador/index.html.twig', [
                    'form' => $form->createView(),
                    'disciplinas' => $this->disciplinas,
                    'userMsg' => $this->userMsg
        ]);
    }

    private function carregarDisciplinas() {
        $pages = $this->pdf->getPages();
        foreach ($pages as $page) {
            $conteudosEmBranco = array();
            if (preg_match('/AULAS PRESENCIAIS MINISTRADAS/', $page->getText())) { // Nova Disciplina
                preg_match('/(?i)(?<=Carga Horária: )(\d)+/', $page->getText(), $cargaHoraria);
                preg_match('/(?i)(?<=Não Presencial: )(\d)+/', $page->getText(), $aulasNaoPresenciais);
                preg_match('/(?i)(?<=Classe: )(\d-)?(.*)\t/', $page->getText(), $nomeDisciplina);
                preg_match('/(?i)(?<=Aulas Presenciais Ministradas: )(\d)+/', $page->getText(), $aulasPresenciais);
                preg_match('/(?i)(?<=Período\/Série: )(.*)\t(?=\sTurma)/', $page->getText(), $serie);
                preg_match('/(?i)(?<=Turma: )(\d)+(\w)\s-/', $page->getText(), $turma);
                preg_match('/(?i)(?<=Curso: )(.*)\n/', $page->getText(), $curso);
                preg_match('/(?i)(?<=Docente: )(.*)\t/', $page->getText(), $professor);
                
                $disciplina = (new Disciplina())
                        ->setAulasNaoPresenciais($aulasNaoPresenciais[0] ?? 0)
                        ->setAulasPresenciais($aulasPresenciais[0] ?? 0)
                        ->setCargaHoraria($cargaHoraria[0])
                        ->setCurso($curso[1])
                        ->setNome(end($nomeDisciplina))
                        ->setProfessor($professor[1])
                        ->setSerie($serie[1])
                        ->setTurma($turma[2]);

                array_push($this->disciplinas, $disciplina);
            }

            preg_match_all('/(\d{1,3}) - (\d{2}\/\d{2}\/\d{4}) \w{3}\t\w\t\d{2}:\d{2}\t\d{2}:\d{2}/', $page->getText(), $conteudosNaPagina);
            preg_match_all('/(\d{1,3}) - (\d{2}\/\d{2}\/\d{4}) \w{3}\t\w\t\d{2}:\d{2}\t\d{2}:\d{2}-\t\t\t/', $page->getText(), $conteudosEmBranco);
            $disciplina->adicionarConteudosRegistrados(count($conteudosNaPagina[0] ?? []) - count($conteudosEmBranco[0] ?? []));
            if (!empty($conteudosEmBranco[2])) {
                $disciplina->setPrimeiroConteudoBranco($conteudosEmBranco[2][0]);
            }

            preg_match_all('/A DEFINIR! - (\d{2}\/\d{2}\/\d{4})/', $page->getText(), $conteudosADefinir);
            $disciplina->adicionarADefinir($conteudosADefinir[1]);

            if (preg_match('/Tipo de Aula:/', $page->getText())) { // Ultima pagina de conteudos da disciplina
                preg_match_all('/(\d{1,3}) - \d{2}\/\d{2}\/\d{4} \w{3}\t\w\t\d{2}:\d{2}\t\d{2}:\d{2}-\t/', $page->getText(), $conteudos);
                $numeroAulas = array_pop($conteudos);
                $disciplina->setConteudosInseridos(array_pop($numeroAulas));
            }
        }
    }

    function validarArquivo(UploadedFile $arquivo) {
        if ($arquivo->getMimeType() == "application/pdf") {
            $parser = new \Smalot\PdfParser\Parser();
            $this->pdf = $parser->parseFile($arquivo->getPathName());
            if (!preg_match('/DIÁRIO DE CLASSE\nConteúdo Registrado/', $this->pdf->getPages()[0]->getText())) {
                $this->userMsg = "O arquivo enviado não corresponde a um <b>Diário de Conteúdos Registrados</b>.";
                return false;
            } else {
                return true;
            }
        } else {
            $this->userMsg = "O arquivo enviado precisa ser do tipo PDF.";
            return false;
        }
    }

}
