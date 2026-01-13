<?php
return [
  "document_registry" => [
    "DOC_001" => "Documento Pessoal (CPF/RG/CNPJ)",
    "DOC_002" => "Procuração",
    "DOC_003" => "Comprovante de Endereço Atualizado",
    "DOC_004" => "Certidão Imobiliária (Matrícula/Transcrições)",
    "DOC_005" => "Projeto Arquitetônico - Arquivo Digital (DWG 2010)",
    "DOC_006" => "Projeto Arquitetônico - Arquivo Digital (PDF)",
    "DOC_007" => "Projeto Arquitetônico Impresso (Físico)",
    "DOC_008" => "ART/RRT/TRT (Projeto e Execução)",
    "DOC_009" => "Guia da Taxa de Licença Quitada",
    "DOC_010" => "Certidão Negativa de Débitos Municipais (CND)",
    "DOC_011" => "Espelho Cadastral (BCI)",
    "DOC_012" => "Projeto As Built - Arquivo Digital (DWG 2010)",
    "DOC_013" => "Projeto As Built - Arquivo Digital (PDF)",
    "DOC_014" => "Laudo Técnico (Estabilidade/Habitabilidade)",
    "DOC_015" => "ART/RRT/TRT (Laudo e As Built)",
    "DOC_016" => "Cópia do Alvará de Construção",
    "DOC_017" => "Cópia da Carta de Habite-se",
    "DOC_018" => "Certidão de Número Anterior",
    "DOC_019" => "Levantamento Topográfico Georreferenciado (DWG 2010)",
    "DOC_020" => "Levantamento Topográfico Georreferenciado (PDF)",
    "DOC_021" => "Memorial Descritivo (PDF)",
    "DOC_022" => "ART/RRT/TRT (Atividade de Medição/Topografia)",
    "DOC_023" => "ART/RRT/TRT (Atividade de Demolição)",
    "DOC_024" => "Alvará de Demolição",
    "DOC_025" => "Relatório de IPTU",
    "DOC_026" => "Nota Técnica e Projeto Aprovado pelo IEPHA",
    "DOC_027" => "Licença Meio Ambiente / CODEMA",
    "DOC_028" => "Certidão de Óbito e Inventário (se falecido)"
  ],
  "processes" => [
    "ALVARA_CONSTRUCAO" => [
      "titulo" => "Alvará de Construção",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004", "DOC_005", "DOC_006", "DOC_008", "DOC_009", "DOC_010", "DOC_011"],
      "docs_excepcionais" => ["DOC_028", "DOC_026", "DOC_027"]
    ],
    "REGULARIZACAO_OBRA" => [
      "titulo" => "Regularização de Obra",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004", "DOC_012", "DOC_013", "DOC_014", "DOC_015", "DOC_009", "DOC_010", "DOC_011"],
      "docs_excepcionais" => ["DOC_028", "DOC_026", "DOC_027", "DOC_016", "DOC_017"]
    ],
    "HABITE_SE" => [
      "titulo" => "Certificado de Conclusão de Obra (Habite-se)",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004", "DOC_016", "DOC_009", "DOC_010", "DOC_011"],
      "docs_excepcionais" => []
    ],
    "CERTIFICADO_AVERBACAO" => [
      "titulo" => "Certificado de Averbação",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004", "DOC_017", "DOC_010", "DOC_011"],
      "docs_excepcionais" => []
    ],
    "ALVARA_DEMOLICAO" => [
      "titulo" => "Alvará de Demolição",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004", "DOC_023", "DOC_009", "DOC_010", "DOC_011"],
      "docs_excepcionais" => []
    ],
    "CERTIDAO_DEMOLICAO" => [
      "titulo" => "Certidão de Demolição",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004", "DOC_024", "DOC_010", "DOC_011"],
      "docs_excepcionais" => []
    ],
    "ALVARA_REFORMA_AMPLIACAO" => [
      "titulo" => "Alvará de Reforma / Ampliação",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004", "DOC_016", "DOC_017", "DOC_012", "DOC_013", "DOC_014", "DOC_008", "DOC_009", "DOC_010", "DOC_011"],
      "docs_excepcionais" => []
    ],
    "CERTIDAO_DECADENCIA" => [
      "titulo" => "Certidão de Decadência",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004"],
      "docs_excepcionais" => ["DOC_017", "DOC_025"]
    ],
    "CERTIDAO_NOME_RUA" => [
      "titulo" => "Certidão de Nome de Rua",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004"],
      "docs_excepcionais" => []
    ],
    "CERTIDAO_LOCALIZACAO" => [
      "titulo" => "Certidão de Localização",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004"],
      "docs_excepcionais" => []
    ],
    "SEGUNDA_VIA_NUMERO" => [
      "titulo" => "2ª Via (Certidão de Número)",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004", "DOC_010", "DOC_011", "DOC_018"],
      "docs_excepcionais" => []
    ],
    "SEGUNDA_VIA_HABITESE" => [
      "titulo" => "2ª Via (Habite-se)",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004", "DOC_010", "DOC_011"],
      "docs_excepcionais" => []
    ],
    "RENOVACAO_ALVARA" => [
      "titulo" => "Renovação de Alvará de Construção",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004", "DOC_010", "DOC_011"],
      "docs_excepcionais" => ["DOC_016"]
    ],
    "SUBSTITUICAO_PROJETO" => [
      "titulo" => "Substituição de Projeto",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004", "DOC_008", "DOC_010", "DOC_011"],
      "docs_excepcionais" => []
    ],
    "CERTIDAO_MEMBRAMENTO" => [
      "titulo" => "Certidão de Remembramento/Membramento",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004", "DOC_019", "DOC_020", "DOC_021", "DOC_022", "DOC_009", "DOC_010", "DOC_011"],
      "docs_excepcionais" => []
    ],
    "CERTIDAO_DESMEMBRAMENTO" => [
      "titulo" => "Certidão de Desmembramento",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004", "DOC_019", "DOC_020", "DOC_021", "DOC_022", "DOC_009", "DOC_010", "DOC_011"],
      "docs_excepcionais" => []
    ],
    "CERTIDAO_UNIFICACAO" => [
      "titulo" => "Certidão de Unificação",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004", "DOC_019", "DOC_020", "DOC_021", "DOC_022", "DOC_009", "DOC_010", "DOC_011"],
      "docs_excepcionais" => []
    ],
    "CERTIDAO_DIVISAO" => [
      "titulo" => "Certidão de Divisão",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004", "DOC_019", "DOC_020", "DOC_021", "DOC_022", "DOC_009", "DOC_010", "DOC_011"],
      "docs_excepcionais" => []
    ],
    "CERTIDAO_RETIFICACAO" => [
      "titulo" => "Certidão de Retificação de Área",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_004", "DOC_019", "DOC_020", "DOC_021", "DOC_010"],
      "docs_excepcionais" => []
    ],
    "USUCAPIAO" => [
      "titulo" => "Usucapião",
      "docs_obrigatorios" => ["DOC_001", "DOC_002", "DOC_003", "DOC_019", "DOC_020", "DOC_022", "DOC_010"],
      "docs_excepcionais": ["DOC_011"]
    ]
  ]
];
