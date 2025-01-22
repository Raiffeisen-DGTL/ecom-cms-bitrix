import shutil
from zipfile import ZipFile
from pathlib import Path

BLOCKSIZE = 1048576
BUILD_PATH = Path('./build')
BASE_PATH = Path('./')

def check_dirpath(dirpath: Path) -> bool:
    # Игнорировать точно папку ./build и части пути включающие .git
    return BUILD_PATH.name not in dirpath.parts and '.git' not in dirpath.parts

def check_filename(filename: Path) -> bool:
    # Точное совпадение для README.md, .gitignore, .py и .log файлы
    return not (filename.name == '.gitignore' or 
                filename.suffix == '.py' or 
                filename.suffix == '.log')

def copy_files_to_build_dir(filenames: list[Path], subbuild_path: Path, encoding: str):
    print(f'copying files to {subbuild_path} in {encoding}', flush=True)
    for filename in filenames:
        print(f'- copying {filename}...', end='', flush=True)
        target_filename = subbuild_path / filename.relative_to(BASE_PATH)
        target_filename.parent.mkdir(parents=True, exist_ok=True)
        try:
            with filename.open('r', encoding='utf-8') as source_file:
                with target_filename.open('w', encoding=encoding) as target_file:
                    while True:
                        contents = source_file.read(BLOCKSIZE)
                        if not contents:
                            break
                        target_file.write(contents)
        except UnicodeDecodeError:
            shutil.copy(filename, target_filename)
        print(f' ✓ done', flush=True)
    print(f'copying files ✓ done', flush=True)

def create_archive(zip_path: Path, subbuild_path: Path, root_folder_name: str):
    print(f'creating archive {zip_path}...', end='', flush=True)
    with ZipFile(zip_path, 'w') as myzip:
        for file in subbuild_path.rglob('*'):
            if file.is_file():
                myzip.write(file, Path(root_folder_name) / file.relative_to(subbuild_path))
    print(f' ✓ done', flush=True)

def main():
    if BUILD_PATH.exists():
        shutil.rmtree(BUILD_PATH)
    BUILD_PATH.mkdir(exist_ok=True)

    filenames = [f for f in BASE_PATH.rglob('*') if f.is_file() and check_dirpath(f.parent) and check_filename(f)]

    subbuild_path = BUILD_PATH / '.last_version'
    subbuild_path.mkdir(parents=True, exist_ok=True)
    copy_files_to_build_dir(filenames, subbuild_path, 'cp1251')
    create_archive(BUILD_PATH / '.last_version.zip', subbuild_path, '.last_version')

    subbuild_path = BUILD_PATH / 'raiffeisenpay-cp1251'
    subbuild_path.mkdir(parents=True, exist_ok=True)
    copy_files_to_build_dir(filenames, subbuild_path, 'cp1251')
    create_archive(BUILD_PATH / 'raiffeisenpay-cp1251.zip', subbuild_path, 'ruraiffeisen.raiffeisenpay')

    subbuild_path = BUILD_PATH / 'raiffeisenpay-utf8'
    subbuild_path.mkdir(parents=True, exist_ok=True)
    copy_files_to_build_dir(filenames, subbuild_path, 'utf-8')
    create_archive(BUILD_PATH / 'raiffeisenpay-utf8.zip', subbuild_path, 'ruraiffeisen.raiffeisenpay')

if __name__ == '__main__':
    main()