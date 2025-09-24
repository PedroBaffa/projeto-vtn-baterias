import csv
import json
import logging
import re
import os
import pandas as pd
from typing import Dict, List

class CSVProductImporter:
    def __init__(self, js_file_path: str = "assets/js/catalogo_produtos.js"):
        self.js_file_path = js_file_path
        self.products = []
        self.load_existing_products()
    
    def load_existing_products(self):
        """Carrega os produtos existentes do arquivo JavaScript"""
        try:
            if os.path.exists(self.js_file_path):
                with open(self.js_file_path, 'r', encoding='utf-8') as file:
                    content = file.read()
                
                # Regex mais robusta para extrair o array de produtos
                pattern = r'const products = \[(.*?)\];'
                match = re.search(pattern, content, re.DOTALL)
                
                if match:
                    products_content = match.group(1).strip()
                    
                    # Se houver conteúdo, faz o parse
                    if products_content and not products_content.startswith('//'):
                        # Divide pelos objetos de produto (procura por chave de fechamento seguida de vírgula ou fim)
                        product_blocks = re.findall(r'\{([^}]+)\}', products_content)
                        
                        for block in product_blocks:
                            try:
                                product = {}
                                # Extrai cada campo
                                brand_match = re.search(r'brand:\s*["\']([^"\']+)["\']', block)
                                title_match = re.search(r'title:\s*["\']([^"\']+)["\']', block)
                                sku_match = re.search(r'sku:\s*["\']([^"\']+)["\']', block)
                                price_match = re.search(r'price:\s*([0-9.]+)', block)
                                image_match = re.search(r'image:\s*["\']([^"\']+)["\']', block)
                                
                                if brand_match and title_match and sku_match and price_match:
                                    product = {
                                        "brand": brand_match.group(1),
                                        "title": title_match.group(1),
                                        "sku": sku_match.group(1),
                                        "price": float(price_match.group(1)),
                                        "image": image_match.group(1) if image_match else f"assets/img/products/battery-{brand_match.group(1)}.jpg"
                                    }
                                    self.products.append(product)
                            except Exception as e:
                                print(f"⚠️  Erro ao processar bloco de produto: {e}")
                                continue
                        
                        print(f"✅ {len(self.products)} produtos carregados")
                    else:
                        print("📭 Nenhum produto encontrado no arquivo")
        except Exception as e:
            print(f"❌ Erro ao carregar produtos: {e}")
            self.products = []
    
    def validate_brand(self, brand: str) -> str:
        """Valida o nome da marca"""
        available_brands = [
            "samsung", "apple", "xiaomi", "lg", "motorola", "huawei", 
            "asus", "lenovo", "nokia", "positivo", "multilaser", 
            "philco", "infinix"
        ]
        brand_lower = brand.lower().strip()
        
        # Tenta corresponder nomes parciais
        for available_brand in available_brands:
            if brand_lower in available_brand or available_brand in brand_lower:
                return available_brand
        
        return brand_lower  # Retorna como está se não encontrar correspondência
    
    def generate_sku(self, brand: str, model: str) -> str:
        """Gera um SKU único"""
        brand_code = brand.upper()[:3]
        model_clean = re.sub(r'[^a-zA-Z0-9]', '', model.upper())[:8]
        
        base_sku = f"VTN-{brand_code}-{model_clean}"
        sku = base_sku
        counter = 1
        
        while any(product['sku'] == sku for product in self.products):
            sku = f"{base_sku}-{counter}"
            counter += 1
        
        return sku
    
    def import_from_csv(self, csv_file_path: str):
        """Importa produtos a partir de um arquivo CSV"""
        try:
            # Tenta ler com pandas primeiro (lida melhor com codificação)
            try:
                df = pd.read_csv(csv_file_path, encoding='utf-8')
            except UnicodeDecodeError:
                df = pd.read_csv(csv_file_path, encoding='latin-1')
            
            print(f"📊 CSV carregado com {len(df)} linhas")
            print(f"📋 Colunas encontradas: {list(df.columns)}")
            
            # Mostra as primeiras linhas para o usuário confirmar a estrutura
            print(f"\n📋 Pré-visualização das 3 primeiras linhas:")
            print(df.head(3).to_string())
            
            # Solicita ao usuário para mapear colunas
            column_mapping = self.get_column_mapping(df.columns.tolist())
            
            if not column_mapping:
                print("❌ Mapeamento de colunas cancelado")
                return
            
            imported_count = 0
            skipped_count = 0
            
            for index, row in df.iterrows():
                try:
                    # Extrai os dados com base no mapeamento
                    brand = str(row[column_mapping['brand']]).strip() if column_mapping['brand'] else "unknown"
                    title = str(row[column_mapping['title']]).strip() if column_mapping['title'] else f"Bateria {brand}"
                    model = str(row[column_mapping['model']]).strip() if column_mapping['model'] else "GENERIC"
                    
                    # Trata o preço
                    price_raw = str(row[column_mapping['price']]).strip() if column_mapping['price'] else "0"
                    price_raw = re.sub(r'[^\d.,]', '', price_raw)  # Remove símbolos monetários
                    price_raw = price_raw.replace(',', '.')
                    
                    try:
                        price = float(price_raw)
                    except ValueError:
                        price = 0.0
                    
                    # Valida a marca
                    brand = self.validate_brand(brand)
                    
                    # Gera o SKU
                    sku = self.generate_sku(brand, model)
                    
                    # Caminho da imagem
                    image = f"assets/img/products/battery-{brand}-{model.lower().replace(' ', '-')}.jpg"
                    
                    # Cria o produto
                    new_product = {
                        "brand": brand,
                        "title": title,
                        "sku": sku,
                        "price": price,
                        "image": image
                    }
                    
                    self.products.append(new_product)
                    imported_count += 1
                    
                    print(f"✅ Importado: {title} (SKU: {sku})")
                    
                except Exception as e:
                    print(f"⚠️  Linha {index + 1} ignorada: {e}")
                    skipped_count += 1
            
            # Salva os produtos
            self.save_products()
            
            print(f"\n📊 RESUMO DA IMPORTAÇÃO:")
            print(f"✅ Importados: {imported_count} produtos")
            print(f"⚠️ Ignorados: {skipped_count} produtos")
            print(f"📋 Total de produtos: {len(self.products)}")
            
        except Exception as e:
            print(f"❌ Erro ao importar CSV: {e}")
    
    def get_column_mapping(self, columns: List[str]) -> Dict[str, str]:
        """Solicita ao usuário o mapeamento das colunas"""
        print(f"\n🗂️  MAPEAMENTO DE COLUNAS")
        print("="*50)
        print("Mapeie as colunas do seu CSV para os campos do produto:")
        print(f"Colunas disponíveis: {columns}")
        
        mapping = {}
        
        # Mapeamento da marca
        print(f"\n🏷️  Coluna da MARCA:")
        for i, col in enumerate(columns):
            print(f"  {i+1}. {col}")
        
        while True:
            try:
                choice = input(f"Selecione a coluna da marca (1-{len(columns)}) ou 'skip': ").strip()
                if choice.lower() == 'skip':
                    mapping['brand'] = None
                    break
                choice_num = int(choice)
                if 1 <= choice_num <= len(columns):
                    mapping['brand'] = columns[choice_num - 1]
                    break
                else:
                    print(f"❌ Escolha inválida. Digite um número de 1 a {len(columns)}")
            except ValueError:
                print("❌ Digite um número ou 'skip'")
        
        # Mapeamento do título
        print(f"\n📝 Coluna do TÍTULO:")
        for i, col in enumerate(columns):
            print(f"  {i+1}. {col}")
        
        while True:
            try:
                choice = input(f"Selecione a coluna do título (1-{len(columns)}) ou 'skip': ").strip()
                if choice.lower() == 'skip':
                    mapping['title'] = None
                    break
                choice_num = int(choice)
                if 1 <= choice_num <= len(columns):
                    mapping['title'] = columns[choice_num - 1]
                    break
                else:
                    print(f"❌ Escolha inválida. Digite um número de 1 a {len(columns)}")
            except ValueError:
                print("❌ Digite um número ou 'skip'")
        
        # Mapeamento do modelo
        print(f"\n📱 Coluna do MODELO:")
        for i, col in enumerate(columns):
            print(f"  {i+1}. {col}")
        
        while True:
            try:
                choice = input(f"Selecione a coluna do modelo (1-{len(columns)}) ou 'skip': ").strip()
                if choice.lower() == 'skip':
                    mapping['model'] = None
                    break
                choice_num = int(choice)
                if 1 <= choice_num <= len(columns):
                    mapping['model'] = columns[choice_num - 1]
                    break
                else:
                    print(f"❌ Escolha inválida. Digite um número de 1 a {len(columns)}")
            except ValueError:
                print("❌ Digite um número ou 'skip'")
        
        # Mapeamento do preço
        print(f"\n💰 Coluna do PREÇO:")
        for i, col in enumerate(columns):
            print(f"  {i+1}. {col}")
        
        while True:
            try:
                choice = input(f"Selecione a coluna do preço (1-{len(columns)}) ou 'skip': ").strip()
                if choice.lower() == 'skip':
                    mapping['price'] = None
                    break
                choice_num = int(choice)
                if 1 <= choice_num <= len(columns):
                    mapping['price'] = columns[choice_num - 1]
                    break
                else:
                    print(f"❌ Escolha inválida. Digite um número de 1 a {len(columns)}")
            except ValueError:
                print("❌ Digite um número ou 'skip'")
        
        # Confirmação do mapeamento
        print(f"\n📋 RESUMO DO MAPEAMENTO:")
        print("="*30)
        for field, column in mapping.items():
            print(f"{field.capitalize()}: {column or 'IGNORADO'}")
        
        confirm = input(f"\n✅ Confirmar mapeamento? (y/N): ").strip().lower()
        if confirm in ['y', 'yes', 's', 'sim']:
            return mapping
        else:
            return None
    
    def save_products(self):
        """Salva os produtos no arquivo JavaScript"""
        try:
            # Lê o arquivo original para preservar o restante do conteúdo
            original_content = ""
            if os.path.exists(self.js_file_path):
                with open(self.js_file_path, 'r', encoding='utf-8') as file:
                    original_content = file.read()
            
            # Converte os produtos para formato JavaScript
            js_products = "const products = [\n"
            
            for i, product in enumerate(self.products):
                js_products += "    {\n"
                js_products += f'        brand: "{product["brand"]}",\n'
                js_products += f'        title: "{product["title"]}",\n'
                js_products += f'        sku: "{product["sku"]}",\n'
                js_products += f'        price: {product["price"]},\n'
                js_products += f'        image: "{product["image"]}"\n'
                js_products += "    }"
                
                if i < len(self.products) - 1:
                    js_products += ","
                js_products += "\n"
            
            js_products += "    // Adicione mais produtos conforme sua planilha\n"
            js_products += "];\n"
            
            # Encontra o restante do conteúdo do arquivo (tudo depois do array de produtos)
            rest_of_file = ""
            if original_content:
                # Procura onde termina o array de produtos
                pattern = r'const products = \[.*?\];'
                match = re.search(pattern, original_content, re.DOTALL)
                if match:
                    # Pega tudo que vem depois do array de produtos
                    rest_of_file = original_content[match.end():]
                else:
                    # Se não encontrar o array, procura a primeira função ou comentário
                    lines = original_content.split('\n')
                    start_index = 0
                    for i, line in enumerate(lines):
                        if line.strip().startswith('//') and 'função' in line.lower():
                            start_index = i
                            break
                        elif line.strip().startswith('function') or line.strip().startswith('document.'):
                            start_index = i
                            break
                    
                    if start_index > 0:
                        rest_of_file = '\n' + '\n'.join(lines[start_index:])
            
            # Escreve o arquivo completo
            with open(self.js_file_path, 'w', encoding='utf-8') as file:
                file.write(js_products)
                if rest_of_file.strip():
                    file.write(rest_of_file)
            
            logging(f"💾 Arquivo salvo: {self.js_file_path}")
            
        except Exception as e:
            print(f"❌ Erro ao salvar arquivo: {e}")

def main():
    """Função principal para importação de CSV"""
    print("📊 IMPORTADOR DE PRODUTOS CSV - GRUPO VTN")
    print("="*50)
    
    importer = CSVProductImporter()
    
    csv_file = input("📁 Digite o caminho do arquivo CSV: ").strip().strip('"')
    
    if not os.path.exists(csv_file):
        print(f"❌ Arquivo não encontrado: {csv_file}")
        return
    
    importer.import_from_csv(csv_file)

if __name__ == "__main__":
    main()
