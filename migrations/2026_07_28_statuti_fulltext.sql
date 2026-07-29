-- Indice FULLTEXT su statuti(titolo, testo), stesso pattern gia' usato per
-- regolamento (vedi CLAUDE.md) — permette a Crystal Bot di cercare anche
-- negli statuti delle razze (pages/api_chatbot.php).
ALTER TABLE statuti ADD FULLTEXT INDEX ft_statuti (titolo, testo);
