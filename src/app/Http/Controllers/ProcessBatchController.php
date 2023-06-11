<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

use Cnab\Remessa\Cnab240\Arquivo;
use DateTime;
use Illuminate\Support\Facades\Log;

class ProcessBatchController extends Controller {

    public const TOPIC_NAME = 'new-cnab-file';

    private $topic;
    private $producer;

    public function processBatches(Request $request) {
        $this->setUpMessageProducer();
        $idClienteInicial = $request->input('idClienteInicial');
        $idClienteFinal = $request->input('idClienteFinal');

        if (empty($idClienteInicial) || empty($idClienteFinal)) {
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

        $this->producer->flush(5000);

        return response()->noContent();
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
            'nosso_numero'      => $cliente['matricula'],
            'numero_documento'  => '1717171',
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

        $filename = 'cliente-' . $cliente['matricula'] . '.txt';
        $arquivo->save(app_path('../../cnab_files_transfer/' . $filename));
        $this->produceMessage($filename);
    }

    private function produceMessage(string $fileName): void {
        $this->topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(['file_name' => $fileName]));
    }

    private function setUpMessageProducer() {
        $conf = new \RdKafka\Conf();
        $conf->set('bootstrap.servers', 'kafka:9092');
        $conf->set('socket.timeout.ms', (string) 50);
        $conf->set('queue.buffering.max.messages', (string) 1000);
        $conf->set('max.in.flight.requests.per.connection', (string) 1);
        $conf->setDrMsgCb(
            function (\RdKafka\Producer $producer, \RdKafka\Message $message): void {
                if ($message->err !== RD_KAFKA_RESP_ERR_NO_ERROR) {
                    Log::error('Error producing message: ' . $message->errstr());
                }
            }
        );
        $conf->set('log_level', (string) LOG_DEBUG);
        $conf->set('debug', 'all');
        $conf->setLogCb(
            function (\RdKafka\Producer $producer, int $level, string $facility, string $message): void {
                Log::debug('Kafka: ' . $message);
            }
        );

        $topicConf = new \RdKafka\TopicConf();
        $topicConf->set('message.timeout.ms', (string) 30000);
        $topicConf->set('request.required.acks', (string) -1);
        $topicConf->set('request.timeout.ms', (string) 5000);

        $this->producer = new \RdKafka\Producer($conf);
        $this->topic = $this->producer->newTopic(self::TOPIC_NAME, $topicConf);
    }
}
