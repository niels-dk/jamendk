# -*- coding: utf-8 -*-
"""pt-BR batch 1 — core UI, auth, sections, dates, canvas, library.

Same convention as the Danish file: the product's own nouns (Dream, Vision,
Mood, Trip, board) and film-industry loanwords Brazilian crews already use in
English (shot, mood board, brand, tag) stay English. Ordinary interface
language is translated.
"""
BR = {
# ── nav ──
'nav.capture': 'Sonho',
'nav.teams': 'Times',
'nav.users': 'Usuários',
'nav.analytics': 'Analytics',
'nav.links': 'Links',
'nav.revenue': 'Receita',
'nav.logout': 'Sair',
'nav.signin': 'Entrar',
'nav.create_account': 'Criar conta',
'nav.pricing': 'Preços',
'nav.dashboard': 'Painel',
'nav.my_account': 'Minha conta',
'nav.language': 'Idioma',
'nav.viewing_as': 'Vendo como :name — Voltar ao admin',
'nav.capture_tip': 'Capture uma ideia — rápido',
'nav.viewing_as_tip': 'Você está navegando como este usuário — clique para voltar à sua conta de admin',

# ── boards ──
'board.dreams': 'Dreams', 'board.visions': 'Visions',
'board.moods': 'Moods', 'board.trips': 'Trips',
'board.dream': 'Dream', 'board.vision': 'Vision',
'board.mood': 'Mood', 'board.trip': 'Trip',

# ── actions ──
'action.save': 'Salvar', 'action.cancel': 'Cancelar', 'action.delete': 'Excluir',
'action.close': 'Fechar', 'action.edit': 'Editar', 'action.add': 'Adicionar',
'action.see_all': 'Ver tudo', 'action.loading': 'Carregando…', 'action.remove': 'Remover',

# ── capture ──
'capture.title': 'Capture',
'capture.placeholder': "Qual é a ideia?\\n\\n“Drone ao nascer do sol sobre as dunas vermelhas, a picape pequena no quadro”",
'capture.hint': 'A primeira linha vira o título. Enter captura — Shift+Enter para nova linha.',
'capture.button': 'Capturar',
'capture.caught': 'Capturado',
'capture.caught_open': 'Capturado — abra ou continue',
'capture.offline': 'Capturado — salvo neste celular, sincroniza quando você voltar',
'capture.offline_pill': 'offline — ainda capturando',
'capture.counter': ':n capturados',
'capture.my_dreams': 'Meus dreams',

# ── auth ──
'auth.welcome_back': 'Bem-vindo de volta',
'auth.email_or_username': 'E-mail ou nome de usuário',
'auth.email': 'E-mail',
'auth.password': 'Senha',
'auth.forgot': 'Esqueceu?',
'auth.signin': 'Entrar',
'auth.new_here': 'Novo por aqui?',
'auth.create_creator': 'Criar uma conta de Creator',
'auth.err_both': 'Informe e-mail e senha.',
'auth.err_invalid': 'E-mail ou senha inválidos.',
'auth.err_expired': 'Sua sessão expirou. Tente novamente.',
'auth.err_deactivated': 'Esta conta foi desativada. Fale com o suporte se isso for um engano.',
'auth.err_unverified': 'Confirme seu endereço de e-mail antes de entrar.',
'auth.resend_link': 'Reenviar o link de confirmação',
'auth.register_title': 'Crie sua conta',
'auth.register_sub': 'Comece a sonhar, planejar e publicar trips em poucos segundos.',
'auth.name': 'Seu nome',
'auth.err_name': 'Diga o seu nome.',
'auth.err_email': 'Esse endereço de e-mail não parece certo.',
'auth.err_short_pass': 'A senha precisa ter pelo menos 6 caracteres.',
'auth.check_inbox': 'Confira sua caixa de entrada — enviamos um link para confirmar seu e-mail. Pode levar um minuto para chegar.',
'auth.forgot_title': 'Esqueceu sua senha?',
'auth.forgot_lead': 'Informe o endereço com que você se cadastrou e enviaremos um link para escolher uma nova.',
'auth.forgot_button': 'Enviar link de redefinição',
'auth.forgot_sent': 'Se existir uma conta para esse endereço, um link de redefinição está a caminho.',
'auth.back_to_signin': 'Voltar para entrar',
'auth.reset_title': 'Escolha uma nova senha',
'auth.new_password': 'Nova senha',
'auth.confirm_password': 'Confirme a nova senha',
'auth.reset_button': 'Salvar nova senha',
'auth.err_nomatch': 'As senhas não conferem.',
'auth.reset_done': 'Senha atualizada — você já pode entrar.',
'auth.link_expired': 'Este link expirou',
'auth.link_expired_lead': 'Links de redefinição duram uma hora e só podem ser usados uma vez — peça um novo abaixo.',
'auth.send_new_link': 'Enviar um novo link',
'auth.min_chars': 'Mínimo de 6 caracteres.',
'auth.have_account': 'Já tem uma conta?',
'auth.verify_title': 'Confirme seu e-mail',
'auth.verify_lead': 'Links de confirmação duram 24 horas e funcionam uma vez. Informe seu endereço e enviaremos um novo.',
'auth.send_new_verify': 'Enviar um novo link',
'auth.err_throttled': 'Tentativas de login em excesso. Espere alguns minutos e tente de novo.',
'auth.signed_out_return': 'Você saiu da conta. Entre novamente para voltar direto para a página em que estava.',

# ── email ──
'email.hi': 'Olá :name,',
'email.footer_note': 'Você recebeu isto porque alguém usou este endereço em :host. Se não foi você, pode ignorar este e-mail com segurança.',
'email.paste_link': 'Ou cole este link no seu navegador:',

# ── sections ──
'sec.basics': 'Básico', 'sec.relations': 'Relations', 'sec.itinerary': 'Roteiro',
'sec.shots': 'Shots', 'sec.goals': 'Metas e marcos', 'sec.budget': 'Orçamento',
'sec.roles': 'Roles & Permissions', 'sec.contacts': 'Contatos',
'sec.documents': 'Documentos', 'sec.workflow': 'Workflow',
'sec.show_on_trip': 'Mostrar na camada Trip', 'sec.hidden_on_trip': 'oculto no trip',
'sec.in_use': 'Em uso',

# ── status ──
'status.saving': 'Salvando…', 'status.saved': 'Salvo',
'status.save_failed': 'Falha ao salvar', 'status.net_error': 'Erro de rede',
'status.delete_failed': 'Falha ao excluir',
'common.untitled': 'Sem título', 'common.failed': 'Falhou',

# ── itinerary ──
'itin.lead': 'O plano dia a dia. Itens marcados como "Mostrar na camada Trip" aparecem no topo da página de trip publicada.',
'itin.add_entry': 'Adicionar item',
'itin.date': 'Data', 'itin.time': 'Hora', 'itin.what': 'O quê',
'itin.what_placeholder': 'ex.: Dirigir até as dunas, filmagem ao amanhecer…',
'itin.location_hint': 'opcional — vira um link de mapa',
'itin.location_placeholder': 'Endereço ou nome do lugar',
'itin.notes': 'Notas',
'itin.notes_placeholder': 'Equipamento a levar, quem chamar, plano B…',
'itin.delete_entry': 'Excluir item',
'itin.none_yet': 'Nenhum item ainda — monte o plano dia a dia.',
'itin.load_failed': 'Falha ao carregar o roteiro.',
'itin.confirm_delete': 'Excluir este item do roteiro?',
'itin.migration': 'Rode db/migrations/2026-07-13_itinerary_budget_items.sql primeiro.',

# ── visibility / workflow / relations ──
'vis.visibility': 'Visibilidade', 'vis.show_dashboard': 'Mostrar no painel',
'wf.status': 'Status',
'wf.notes_placeholder': 'Qualquer coisa que valha acompanhar — travas, próximos passos, decisões…',
'wf.show_section': 'Mostrar seção', 'wf.complete': 'Concluído',
'rel.search_placeholder': 'Busque pelo nome ou cole um ID…',
'rel.one_mood_hint': 'Apenas um mood board por vision. Para trocar, remova o atual primeiro.',

# ── basics ──
'basics.title': 'Básico da Vision',
'basics.start': 'Data de início', 'basics.end': 'Data de término',
'basics.publishing': 'Publicação do trip', 'basics.publish': 'Publicar como Trip',
'basics.never': 'Nunca expira', 'basics.exp7': 'Expirar em 7 dias', 'basics.exp30': 'Expirar em 30 dias',
'basics.sections_hint': 'Escolha quais seções aparecem quando este trip for publicado.',
'basics.share_link': 'Link público de compartilhamento — qualquer pessoa com ele pode ver o trip',
'basics.copy': 'Copiar', 'basics.copied': 'Copiado', 'basics.new_link': 'Novo link',
'basics.trip_master_tip': 'Chave geral — quando desligada, a página do trip fica indisponível.',
'basics.trip_master_help': 'Chave geral — quando desligada, /trips/:slug mostra "não publicado".',
'basics.mint_tip': 'Gerar um link novo — o antigo para de funcionar',
'basics.expires': 'Expira em :date',
'basics.confirm_mint': 'Gerar um link novo? O atual para de funcionar imediatamente.',

# ── budget ──
'budget.currency': 'Moeda', 'budget.search_currency': 'Buscar moeda…',
'budget.line_items': 'Itens', 'budget.line_items_hint': 'viagem, equipamento, elenco…',
'budget.add_line': 'Adicionar item', 'budget.paid': 'Pago?',
'budget.total': 'Total', 'budget.sum_of_lines': '= soma dos itens',
'budget.lines': 'itens', 'budget.remaining': 'restante', 'budget.over_by': 'ACIMA em',

# ── contacts ──
'contacts.add': 'Adicionar contato', 'contacts.add_field': 'Adicionar campo',
'contacts.fields': 'Campos', 'contacts.flags': 'Marcadores',
'contacts.current': 'Atual', 'contacts.main': 'Principal',
'contacts.none_yet': 'Nenhum contato ainda.',
'contacts.load_failed': 'Falha ao carregar contatos.',
'contacts.confirm_delete': 'Excluir este contato?',
'contacts.custom': 'Personalizado…', 'contacts.custom_prompt': 'Digite o nome do campo',
'contacts.unnamed': '(sem nome)',
'contacts.f_name': 'Nome', 'contacts.f_company': 'Empresa', 'contacts.f_address': 'Endereço',
'contacts.f_mobile': 'Celular', 'contacts.f_email': 'E-mail', 'contacts.f_country': 'País',
'contacts.load_one_failed': 'Falha ao carregar o contato',

# ── anchors ──
'anchor.locations': 'Locations', 'anchor.brands': 'Brands', 'anchor.people': 'Pessoas',
'anchor.seasons': 'Estações', 'anchor.time': 'Época', 'anchor.seasons_time': 'Estações / Época',

# ── documents ──
'docs.upload': 'Enviar', 'docs.download': 'Baixar', 'docs.none_yet': 'Nenhum documento ainda.',
'docs.draft': 'Rascunho', 'docs.waiting_brand': 'Aguardando brand',
'docs.final': 'Final', 'docs.signed': 'Assinado',
'docs.no_group': '— Sem grupo —', 'docs.new_group': 'Novo grupo…',
'docs.new_group_prompt': 'Nome do novo grupo:',
'docs.trip_toggle_tip': 'Clique para mostrar ou ocultar na camada Trip',
'docs.on_trip': 'No trip', 'docs.off_trip': 'Fora do trip',
'docs.update_failed': 'Falha ao atualizar', 'docs.status_failed': 'Falha ao atualizar o status',
'docs.create_failed': 'Falha ao criar', 'docs.choose_file': 'Escolha um arquivo primeiro.',
'docs.uploading': 'Enviando…', 'docs.uploaded': 'Enviado', 'docs.upload_failed': 'Falha no envio',

# ── dates ──
'date.medium': ':d de :m de :y',
'month.jan': 'jan', 'month.feb': 'fev', 'month.mar': 'mar', 'month.apr': 'abr',
'month.may': 'mai', 'month.jun': 'jun', 'month.jul': 'jul', 'month.aug': 'ago',
'month.sep': 'set', 'month.oct': 'out', 'month.nov': 'nov', 'month.dec': 'dez',
'day.mon': 'segunda-feira', 'day.tue': 'terça-feira', 'day.wed': 'quarta-feira',
'day.thu': 'quinta-feira', 'day.fri': 'sexta-feira', 'day.sat': 'sábado', 'day.sun': 'domingo',
'time.just_now': 'agora mesmo', 'time.in_a_moment': 'daqui a pouco',
'time.min_ago': 'há :n min', 'time.in_min': 'em :n min',
'time.hr_ago': 'há :n h', 'time.in_hr': 'em :n h',
'time.yesterday': 'ontem', 'time.tomorrow': 'amanhã', 'time.today': 'hoje',
'time.day_ago': 'há 1 dia', 'time.days_ago': 'há :n dias', 'time.in_days': 'em :n dias',

# ── footer ──
'footer.blurb': 'Capture a ideia, transforme em plano e abra a shot list quando estiver no lugar. Feito para cineastas e criadores.',
'footer.instagram': 'Instagram', 'footer.email': 'E-mail', 'footer.product': 'Produto',
'footer.how_it_works': 'Como funciona', 'footer.support': 'Suporte', 'footer.help': 'Ajuda',
'footer.contact': 'Contato', 'footer.legal': 'Jurídico',
'footer.privacy': 'Política de privacidade', 'footer.terms': 'Termos',
'footer.made_in': 'Feito na Dinamarca', 'footer.tested_in': 'testado no Brasil',

# ── dream pages ──
'dream.detail': 'Detalhes do Dream', 'dream.create': 'Criar um Dream',
'dream.edit': 'Editar Dream', 'dream.name': 'Nome do Dream',
'dream.inspiration': 'Inspiração', 'dream.save': 'Salvar Dream',
'dream.save_view': 'Salvar e ver o board',
'dream.promote': 'Promover a Vision',
'dream.promote_tip': 'Cria uma Vision a partir deste Dream, copiando título, descrição e anchors',
'dream.promoted': 'Promovido a Vision', 'dream.open_vision': 'Abrir Vision',
'dream.title_placeholder': 'Título do Dream', 'dream.desc_placeholder': 'Descreva seu sonho…',

# ── canvas ──
'canvas.select': 'Selecionar', 'canvas.pan': 'Mover', 'canvas.zoom_out': 'Zoom',
'canvas.zoom_in': 'Zoom', 'canvas.reset': 'Redefinir', 'canvas.text': 'Texto',
'canvas.frame': 'Frame', 'canvas.resize': 'Redimensionar', 'canvas.connector': 'Conector',
'canvas.snap': 'Snap', 'canvas.tool': 'Ferramenta', 'canvas.media': 'Mídia',
'canvas.drag_to_move': 'Arraste para mover', 'canvas.arrow_none': 'Sem seta',
'canvas.arrow_start': 'Seta no início (sentido contrário)',
'canvas.swap': 'Inverter de / para', 'canvas.edit_label': 'Editar rótulo',
'canvas.select_frame': 'Selecione um frame primeiro.',
'canvas.select_frame_media': 'Selecione um frame para anexar mídia.',
'canvas.search_media': 'Buscar por nome do arquivo, provedor, tags…',
'canvas.media_load_failed': 'Não foi possível carregar as mídias.',
'canvas.no_media': 'Nenhuma mídia encontrada.',
'canvas.no_board_media': 'Nenhuma mídia neste board ainda — tente Todos os arquivos de mídia.',

# ── board states ──
'filter.active': 'Ativos', 'filter.archived': 'Arquivados', 'filter.trash': 'Lixeira',
'filter.promoted': 'Promovidos', 'filter.shared_with_me': 'Compartilhados comigo',
'filter.shared_by_me': 'Compartilhados por mim',

# ── media library ──
'lib.edit_tags': 'Editar tags', 'lib.current_tags': 'Tags atuais',
'lib.none_yet': 'Nenhuma ainda', 'lib.add_tag': 'Adicionar tag',
'lib.tag_placeholder': 'Digite e pressione Enter',
'lib.change_group': 'Trocar grupo', 'lib.choose_group': 'Escolher grupo existente',
'lib.new_group_ph': 'Nome do novo grupo', 'lib.url': 'URL',
'lib.url_placeholder': 'Cole um link do YouTube, Vimeo ou qualquer URL…',
'lib.no_preview': 'Sem prévia', 'lib.attach': 'Anexar a este board',
'lib.detach': 'Remover deste board', 'lib.no_files': 'Nenhum arquivo ainda.',
}
