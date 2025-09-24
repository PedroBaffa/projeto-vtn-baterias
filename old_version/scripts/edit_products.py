import json
import re
import os
from typing import Dict, List, Optional

class ProductEditor:
    def __init__(self, js_file_path: str = "assets/js/catalogo_produtos.js"):
        self.js_file_path = js_file_path
        self.products = []
        self.load_existing_products()
    
    def load_existing_products(self):
        """Load existing products from the JavaScript file"""
        try:
            if os.path.exists(self.js_file_path):
                with open(self.js_file_path, 'r', encoding='utf-8') as file:
                    content = file.read()
                
                # More robust regex to extract the products array
                pattern = r'const products = \[(.*?)\];'
                match = re.search(pattern, content, re.DOTALL)
                
                if match:
                    products_content = match.group(1).strip()
                    
                    # If there's content, parse it
                    if products_content and not products_content.startswith('//'):
                        # Split by product objects (look for closing brace followed by comma or end)
                        product_blocks = re.findall(r'\{([^}]+)\}', products_content)
                        
                        for block in product_blocks:
                            try:
                                product = {}
                                # Extract each field
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
                                print(f"⚠️  Error parsing product block: {e}")
                                continue
                        
                        print(f"✅ Loaded {len(self.products)} existing products")
                    else:
                        print("📭 No existing products found in file")
                else:
                    print("⚠️  No products array found in file")
            else:
                print("⚠️  JavaScript file not found")
        except Exception as e:
            print(f"❌ Error loading products: {e}")
            self.products = []
    
    def get_available_brands(self) -> List[str]:
        """Get list of available brands"""
        brands = [
            "samsung", "apple", "xiaomi", "lg", "motorola", "huawei", 
            "asus", "lenovo", "nokia", "positivo", "multilaser", 
            "philco", "infinix"
        ]
        return brands
    
    def validate_brand(self, brand: str) -> str:
        """Validate and return the brand in lowercase"""
        available_brands = self.get_available_brands()
        brand_lower = brand.lower().strip()
        
        if brand_lower not in available_brands:
            print(f"⚠️  Brand '{brand}' not in available brands.")
            print(f"Available brands: {', '.join(available_brands)}")
            return None
        
        return brand_lower
    
    def list_products(self, show_numbers: bool = True) -> bool:
        """List all existing products"""
        if not self.products:
            print("📭 Nenhum produto encontrado.")
            return False
        
        print(f"\n📋 PRODUTOS CADASTRADOS ({len(self.products)} total):")
        print("="*90)
        
        for i, product in enumerate(self.products, 1):
            if show_numbers:
                print(f"{i:2d}. {product['brand'].upper()} | {product['title']}")
            else:
                print(f"    {product['brand'].upper()} | {product['title']}")
            print(f"    SKU: {product['sku']} | Preço: R$ {product['price']:.2f}")
            print(f"    Imagem: {product['image']}")
            print("-" * 90)
        
        return True
    
    def search_products(self, query: str) -> List[int]:
        """Search products by brand, title, or SKU"""
        query_lower = query.lower()
        matches = []
        
        for i, product in enumerate(self.products):
            if (query_lower in product['brand'].lower() or 
                query_lower in product['title'].lower() or 
                query_lower in product['sku'].lower()):
                matches.append(i)
        
        return matches
    
    def select_product(self) -> Optional[int]:
        """Interactive product selection"""
        if not self.products:
            print("📭 Nenhum produto disponível.")
            return None
        
        while True:
            print(f"\n🔍 SELECIONAR PRODUTO:")
            print("1. 📋 Ver todos os produtos")
            print("2. 🔍 Buscar produto")
            print("3. 🔢 Selecionar por número")
            print("4. 🚪 Voltar")
            
            choice = input(f"\n🎯 Escolha uma opção (1-4): ").strip()
            
            if choice == '1':
                if self.list_products():
                    try:
                        product_num = int(input(f"\n📝 Digite o número do produto (1-{len(self.products)}): "))
                        if 1 <= product_num <= len(self.products):
                            return product_num - 1
                        else:
                            print(f"❌ Número inválido. Digite entre 1 e {len(self.products)}")
                    except ValueError:
                        print("❌ Digite um número válido")
            
            elif choice == '2':
                query = input("🔍 Digite parte do nome, marca ou SKU: ").strip()
                if query:
                    matches = self.search_products(query)
                    if matches:
                        print(f"\n🎯 Encontrados {len(matches)} produto(s):")
                        print("="*90)
                        for i, match_idx in enumerate(matches, 1):
                            product = self.products[match_idx]
                            print(f"{i:2d}. {product['brand'].upper()} | {product['title']}")
                            print(f"    SKU: {product['sku']} | Preço: R$ {product['price']:.2f}")
                            print("-" * 90)
                        
                        try:
                            selection = int(input(f"\n📝 Selecione o produto (1-{len(matches)}): "))
                            if 1 <= selection <= len(matches):
                                return matches[selection - 1]
                            else:
                                print(f"❌ Número inválido")
                        except ValueError:
                            print("❌ Digite um número válido")
                    else:
                        print("❌ Nenhum produto encontrado")
            
            elif choice == '3':
                try:
                    product_num = int(input(f"📝 Digite o número do produto (1-{len(self.products)}): "))
                    if 1 <= product_num <= len(self.products):
                        return product_num - 1
                    else:
                        print(f"❌ Número inválido. Digite entre 1 e {len(self.products)}")
                except ValueError:
                    print("❌ Digite um número válido")
            
            elif choice == '4':
                return None
            
            else:
                print("❌ Opção inválida")
    
    def edit_product(self):
        """Edit an existing product"""
        print("\n" + "="*50)
        print("✏️  EDITAR PRODUTO")
        print("="*50)
        
        product_idx = self.select_product()
        if product_idx is None:
            return
        
        product = self.products[product_idx]
        
        print(f"\n📋 PRODUTO SELECIONADO:")
        print("="*50)
        print(f"🏷️  Marca: {product['brand'].capitalize()}")
        print(f"📝 Título: {product['title']}")
        print(f"🔖 SKU: {product['sku']}")
        print(f"💰 Preço: R$ {product['price']:.2f}")
        print(f"🖼️  Imagem: {product['image']}")
        
        print(f"\n✏️  O que deseja editar?")
        print("1. 🏷️  Marca")
        print("2. 📝 Título")
        print("3. 🔖 SKU")
        print("4. 💰 Preço")
        print("5. 🖼️  Imagem")
        print("6. 🔄 Editar tudo")
        print("7. 🚪 Cancelar")
        
        choice = input(f"\n🎯 Escolha uma opção (1-7): ").strip()
        
        changes_made = False
        
        if choice == '1':  # Edit brand
            print(f"\n🏷️  Marcas disponíveis:")
            brands = self.get_available_brands()
            for i, brand in enumerate(brands, 1):
                print(f"  {i:2d}. {brand.capitalize()}")
            
            while True:
                brand_input = input(f"\n🏷️  Digite o número da marca ou nome (atual: {product['brand']}): ").strip()
                if not brand_input:
                    break
                
                if brand_input.isdigit():
                    brand_num = int(brand_input)
                    if 1 <= brand_num <= len(brands):
                        product['brand'] = brands[brand_num - 1]
                        changes_made = True
                        break
                    else:
                        print(f"❌ Número inválido")
                else:
                    new_brand = self.validate_brand(brand_input)
                    if new_brand:
                        product['brand'] = new_brand
                        changes_made = True
                        break
        
        elif choice == '2':  # Edit title
            new_title = input(f"📝 Novo título (atual: {product['title']}): ").strip()
            if new_title:
                product['title'] = new_title
                changes_made = True
        
        elif choice == '3':  # Edit SKU
            new_sku = input(f"🔖 Novo SKU (atual: {product['sku']}): ").strip()
            if new_sku:
                # Check if SKU already exists
                if any(p['sku'] == new_sku for i, p in enumerate(self.products) if i != product_idx):
                    print("❌ SKU já existe!")
                else:
                    product['sku'] = new_sku
                    changes_made = True
        
        elif choice == '4':  # Edit price
            while True:
                try:
                    price_input = input(f"💰 Novo preço (atual: R$ {product['price']:.2f}): R$ ").strip()
                    if not price_input:
                        break
                    price_input = price_input.replace(',', '.')
                    new_price = float(price_input)
                    product['price'] = new_price
                    changes_made = True
                    break
                except ValueError:
                    print("❌ Preço inválido. Use formato: 149.90")
        
        elif choice == '5':  # Edit image
            new_image = input(f"🖼️  Nova imagem (atual: {product['image']}): ").strip()
            if new_image:
                product['image'] = new_image
                changes_made = True
        
        elif choice == '6':  # Edit everything
            changes_made = self.edit_all_fields(product, product_idx)
        
        elif choice == '7':  # Cancel
            print("❌ Edição cancelada")
            return
        
        else:
            print("❌ Opção inválida")
            return
        
        if changes_made:
            print(f"\n📋 PRODUTO ATUALIZADO:")
            print("="*50)
            print(f"🏷️  Marca: {product['brand'].capitalize()}")
            print(f"📝 Título: {product['title']}")
            print(f"🔖 SKU: {product['sku']}")
            print(f"💰 Preço: R$ {product['price']:.2f}")
            print(f"🖼️  Imagem: {product['image']}")
            
            confirm = input(f"\n✅ Salvar alterações? (s/N): ").strip().lower()
            if confirm in ['s', 'sim', 'y', 'yes']:
                self.save_products()
                print("✅ Produto atualizado com sucesso!")
            else:
                print("❌ Alterações não salvas")
        else:
            print("ℹ️  Nenhuma alteração feita")
    
    def edit_all_fields(self, product: Dict, product_idx: int) -> bool:
        """Edit all fields of a product"""
        changes_made = False
        
        # Brand
        print(f"\n🏷️  Marcas disponíveis:")
        brands = self.get_available_brands()
        for i, brand in enumerate(brands, 1):
            print(f"  {i:2d}. {brand.capitalize()}")
        
        brand_input = input(f"\n🏷️  Nova marca (atual: {product['brand']}, Enter para manter): ").strip()
        if brand_input:
            if brand_input.isdigit():
                brand_num = int(brand_input)
                if 1 <= brand_num <= len(brands):
                    product['brand'] = brands[brand_num - 1]
                    changes_made = True
            else:
                new_brand = self.validate_brand(brand_input)
                if new_brand:
                    product['brand'] = new_brand
                    changes_made = True
        
        # Title
        new_title = input(f"📝 Novo título (atual: {product['title']}, Enter para manter): ").strip()
        if new_title:
            product['title'] = new_title
            changes_made = True
        
        # SKU
        new_sku = input(f"🔖 Novo SKU (atual: {product['sku']}, Enter para manter): ").strip()
        if new_sku:
            if any(p['sku'] == new_sku for i, p in enumerate(self.products) if i != product_idx):
                print("❌ SKU já existe! Mantendo SKU atual.")
            else:
                product['sku'] = new_sku
                changes_made = True
        
        # Price
        while True:
            try:
                price_input = input(f"💰 Novo preço (atual: R$ {product['price']:.2f}, Enter para manter): R$ ").strip()
                if not price_input:
                    break
                price_input = price_input.replace(',', '.')
                new_price = float(price_input)
                product['price'] = new_price
                changes_made = True
                break
            except ValueError:
                print("❌ Preço inválido. Use formato: 149.90")
                retry = input("Tentar novamente? (s/N): ").strip().lower()
                if retry not in ['s', 'sim', 'y', 'yes']:
                    break
        
        # Image
        new_image = input(f"🖼️  Nova imagem (atual: {product['image']}, Enter para manter): ").strip()
        if new_image:
            product['image'] = new_image
            changes_made = True
        
        return changes_made
    
    def delete_product(self):
        """Delete an existing product"""
        print("\n" + "="*50)
        print("🗑️  EXCLUIR PRODUTO")
        print("="*50)
        
        product_idx = self.select_product()
        if product_idx is None:
            return
        
        product = self.products[product_idx]
        
        print(f"\n⚠️  PRODUTO A SER EXCLUÍDO:")
        print("="*50)
        print(f"🏷️  Marca: {product['brand'].capitalize()}")
        print(f"📝 Título: {product['title']}")
        print(f"🔖 SKU: {product['sku']}")
        print(f"💰 Preço: R$ {product['price']:.2f}")
        print(f"🖼️  Imagem: {product['image']}")
        
        print(f"\n⚠️  ATENÇÃO: Esta ação não pode ser desfeita!")
        confirm = input(f"🗑️  Confirma a exclusão? Digite 'EXCLUIR' para confirmar: ").strip()
        
        if confirm == 'EXCLUIR':
            deleted_product = self.products.pop(product_idx)
            self.save_products()
            print(f"✅ Produto '{deleted_product['title']}' excluído com sucesso!")
        else:
            print("❌ Exclusão cancelada")
    
    def bulk_delete(self):
        """Delete multiple products"""
        print("\n" + "="*50)
        print("🗑️  EXCLUSÃO EM LOTE")
        print("="*50)
        
        if not self.list_products():
            return
        
        print(f"\n📝 Digite os números dos produtos a excluir (separados por vírgula):")
        print("Exemplo: 1,3,5 ou 1-5 para intervalo")
        
        selection = input("🎯 Produtos para excluir: ").strip()
        if not selection:
            print("❌ Nenhum produto selecionado")
            return
        
        # Parse selection
        indices_to_delete = set()
        
        try:
            parts = selection.split(',')
            for part in parts:
                part = part.strip()
                if '-' in part:
                    # Range selection (e.g., 1-5)
                    start, end = map(int, part.split('-'))
                    indices_to_delete.update(range(start-1, end))
                else:
                    # Single number
                    indices_to_delete.add(int(part) - 1)
            
            # Validate indices
            valid_indices = [i for i in indices_to_delete if 0 <= i < len(self.products)]
            
            if not valid_indices:
                print("❌ Nenhum produto válido selecionado")
                return
            
            # Show products to be deleted
            print(f"\n⚠️  PRODUTOS A SEREM EXCLUÍDOS ({len(valid_indices)} total):")
            print("="*70)
            
            products_to_delete = []
            for idx in sorted(valid_indices):
                product = self.products[idx]
                products_to_delete.append(product)
                print(f"• {product['brand'].upper()} | {product['title']} (SKU: {product['sku']})")
            
            print(f"\n⚠️  ATENÇÃO: Esta ação não pode ser desfeita!")
            confirm = input(f"🗑️  Confirma a exclusão de {len(valid_indices)} produto(s)? Digite 'EXCLUIR' para confirmar: ").strip()
            
            if confirm == 'EXCLUIR':
                # Delete in reverse order to maintain indices
                for idx in sorted(valid_indices, reverse=True):
                    self.products.pop(idx)
                
                self.save_products()
                print(f"✅ {len(valid_indices)} produto(s) excluído(s) com sucesso!")
            else:
                print("❌ Exclusão cancelada")
                
        except ValueError:
            print("❌ Formato inválido. Use números separados por vírgula ou intervalos (ex: 1,3,5 ou 1-5)")
    
    def save_products(self):
        """Save products back to JavaScript file"""
        try:
            # Read the original file to preserve the rest of the content
            original_content = ""
            if os.path.exists(self.js_file_path):
                with open(self.js_file_path, 'r', encoding='utf-8') as file:
                    original_content = file.read()
            
            # Convert products to JavaScript format
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
            
            # Find the rest of the file content (everything after the products array)
            rest_of_file = ""
            if original_content:
                # Find where the products array ends
                pattern = r'const products = \[.*?\];'
                match = re.search(pattern, original_content, re.DOTALL)
                if match:
                    # Get everything after the products array
                    rest_of_file = original_content[match.end():]
                else:
                    # If no products array found, find the first function or comment
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
            
            # Write complete file
            with open(self.js_file_path, 'w', encoding='utf-8') as file:
                file.write(js_products)
                if rest_of_file.strip():
                    file.write(rest_of_file)
            
            print(f"💾 Arquivo salvo: {self.js_file_path}")
            
        except Exception as e:
            print(f"❌ Erro ao salvar arquivo: {e}")

def main():
    """Main function"""
    print("✏️  EDITOR DE PRODUTOS - GRUPO VTN")
    print("="*50)
    
    editor = ProductEditor()
    
    if not editor.products:
        print("📭 Nenhum produto encontrado para editar.")
        return
    
    while True:
        print(f"\n📋 MENU PRINCIPAL:")
        print("1. 📋 Listar produtos")
        print("2. 🔍 Buscar produto")
        print("3. ✏️  Editar produto")
        print("4. 🗑️  Excluir produto")
        print("5. 🗑️  Exclusão em lote")
        print("6. 🚪 Sair")
        
        choice = input(f"\n🎯 Escolha uma opção (1-6): ").strip()
        
        if choice == '1':
            editor.list_products()
        
        elif choice == '2':
            query = input("🔍 Digite parte do nome, marca ou SKU: ").strip()
            if query:
                matches = editor.search_products(query)
                if matches:
                    print(f"\n🎯 Encontrados {len(matches)} produto(s):")
                    print("="*90)
                    for match_idx in matches:
                        product = editor.products[match_idx]
                        print(f"    {product['brand'].upper()} | {product['title']}")
                        print(f"    SKU: {product['sku']} | Preço: R$ {product['price']:.2f}")
                        print("-" * 90)
                else:
                    print("❌ Nenhum produto encontrado")
        
        elif choice == '3':
            editor.edit_product()
        
        elif choice == '4':
            editor.delete_product()
        
        elif choice == '5':
            editor.bulk_delete()
        
        elif choice == '6':
            print("👋 Até logo!")
            break
        
        else:
            print("❌ Opção inválida. Tente novamente.")

if __name__ == "__main__":
    main()
