<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

use Cnab\Remessa\Cnab240\Arquivo;
use DateTime;

class ProcessBatchController extends Controller {

    public function processBatches(Request $request) {
        $idClienteInicial = $request->input('idClienteInicial');
        $idClienteFinal = $request->input('idClienteFinal');

        if (!$idClienteInicial || !$idClienteFinal) {
            return response()->json(['error' => 'Missing required parameters "idClienteInicial" and "idClienteFinal"'], 400);
        }

        if (!is_numeric($idClienteInicial) || !is_numeric($idClienteFinal)) {
            return response()->json(['error' => 'Parameters "idClienteInicial" and "idClienteFinal" must be numeric'], 400);
        }

        $idClienteInicial = intval($idClienteInicial);
        $idClienteFinal = intval($idClienteFinal);

        if (!($idClienteFinal > $idClienteInicial)) {
            return response()->json(['error' => 'Parameter "idClienteFinal" must be greater than "idClienteInicial"'], 400);
        }

        if (!($idClienteFinal - $idClienteInicial <= 100)) {
            return response()->json(['error' => 'Parameter "idClienteFinal" must be at most 100 greater than "idClienteInicial"'], 400);
        }

        $clientes = Cliente::whereBetween('id', [$idClienteInicial, $idClienteFinal])->get()->toArray();

        if (!count($clientes)) {
            return response()->json(['error' => 'No clients found'], 404);
        }

        foreach ($clientes as $cliente) {
            $this->generateCnabFile($cliente);
        }

    }

    private function generateCnabFile(array $cliente): void {
        $codigoBanco = \Cnab\Banco::BANCO_DO_BRASIL;
        $arquivo = new Arquivo($codigoBanco);

        $arquivo->configure([
            'data_geracao'  => new DateTime(),
            'data_gravacao' => new DateTime(),
            'nome_fantasia' => 'Nome Fantasia',
            'razao_social'  => 'Razão social',
            'cnpj'          => '92213268000120',
            'banco'         => $codigoBanco,
            'logradouro'    => 'Logradouro',
            'numero'        => 'Número do endereço',
            'bairro'        => 'Jardim Nicea', 
            'cidade'        => 'Itaquaquecetuba',
            'uf'            => 'SP',
            'cep'           => '08589319',
            'agencia'       => '4020',
            'codigo_convenio' => '123',
            'codigo_carteira' => '19',
            'variacao_carteira' => '19',
            'operacao'      => '01',
            'numero_sequencial_arquivo' => '1',
            'agencia_dv'   => '0',
            'conta'         => '41234',
            'conta_dv'     => '2'
        ]);

        $arquivo->insertDetalhe([
            'codigo_de_ocorrencia' => 1, // 1 = Entrada de título
            'codigo_carteira'      => '1',
            'registrado'           => true,
            'aceite'               => 'N',
            'nosso_numero'      => '1234567',
            'numero_documento'  => '1234567',
            'carteira'          => '109',
            'especie'           => \Cnab\Especie::BB_DUPLICATA_DE_SERVICO,
            'valor'             => $cliente['valor'], // Valor do boleto
            'instrucao1'        => 2, // 1 = Protestar com (Prazo) dias, 2 = Devolver após (Prazo) dias
            'instrucao2'        => 0,
            'sacado_nome'       => $cliente['nome'],
            'sacado_tipo'       => 'cpf',
            'sacado_cpf'        => $cliente['cpf'],
            'sacado_logradouro' => 'Logradouro cliente',
            'sacado_bairro'     => 'Bairro cliente',
            'sacado_cep'        => '11111222',
            'sacado_cidade'     => 'Cidade cliente',
            'sacado_uf'         => 'SP',
            'data_vencimento'   => new DateTime('2023-06-08'),
            'data_cadastro'     => new DateTime($cliente['inclusao']),
            'juros_de_um_dia'     => 0.10,
            'data_desconto'       => new DateTime('2023-06-08'),
            'valor_desconto'      => 10.0,
            'prazo'               => 10,
            'taxa_de_permanencia' => '0',
            'mensagem'            => 'matrícula ' . $cliente['matricula'],
            'data_multa'          => new DateTime('2023-06-08'),
            'valor_multa'         => 10.0,
        ]);

        $arquivo->save(app_path('../../cnab_files_transfer/cliente-' . $cliente['id'] . '.txt'));

    }
}
